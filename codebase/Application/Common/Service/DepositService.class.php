<?php

namespace Common\Service;

use Think\Model;

class DepositService
{
    public function handleDeposit($transaction)
    {
        $mo = new Model();
          $existingTransaction = $mo->table('codono_pending')
            ->where(['userid' => $transaction['userid'], 'txid' => $transaction['txid']])
            ->find();

        if ($existingTransaction) {
            return ['status' => 0, 'msg' => 'Duplicate transaction. This transaction has already been processed.'];
        }
        $pendingData = [
            'userid' => $transaction['userid'],
            'username' => $transaction['username'],
            'coinname' => $transaction['coinname'],
            'chain' => $transaction['chain'],
            'type' => $transaction['type'],
            'txid' => $transaction['txid'],
            'memo' => $transaction['memo'],
            'num' => $transaction['num'],
            'mum' => $transaction['mum'],
            'fee' => $transaction['fee'],
            'addtime' => time(),
            'status' => 0, // Initial status
            'aml_status' => 0 // AML status pending
        ];

        $mo->table('codono_pending')->add($pendingData);

        // Notify the user about the pending deposit
        $this->addNotification($transaction['userid'], 'Deposit Pending', 'Your deposit is pending AML verification.');
    }

    private function addNotification($userid, $subject, $content)
    {
        $notification = [
            'userid' => $userid,
            'subject' => $subject,
            'content' => $content,
            'status' => 0, // Unsent
            'addtime' => time()
        ];
        M('Notification')->add($notification);
    }

    public function approveDeposit($pendingId)
    {
        $mo = new Model();
        $pendingTransaction = $mo->table('codono_pending')->where(['id' => $pendingId, 'aml_status' => 0])->find();
        if (!$pendingTransaction) {
            return ['status'=>0,'Pending transaction not found or already processed'];
        }
         // Check if the transaction already exists in the codono_myzr table
         $existingTransaction = $mo->table('codono_myzr')
         ->where(['userid' => $pendingTransaction['userid'], 'txid' => $pendingTransaction['txid']])
         ->find();

         if ($existingTransaction) {
            $info=$mo->table('codono_pending')->where(['id' => $pendingId, 'aml_status' => 0])->setField('aml_status', 1);    
            return ['status'=>0,'Duplicate transaction. This transaction has already been deposited.'];
         }

        $mo->startTrans();
        try {
           
            // Verify AML status (e.g., call an external AML provider API)
            $amlApproved = $this->checkAmlStatus($pendingTransaction['txid']);
            if ($amlApproved) {
                $pendingTransaction['status'] = 1; // Mark as completed
                $pendingTransaction['aml_status'] = 1; // AML approved
                $mo->table('codono_pending')->save($pendingTransaction);

                $depositData = [
                    'userid' => $pendingTransaction['userid'],
                    'username' => $pendingTransaction['username'],
                    'coinname' => $pendingTransaction['coinname'],
                    'chain' => $pendingTransaction['chain'],
                    'type' => $pendingTransaction['type'],
                    'txid' => $pendingTransaction['txid'],
                    'memo' => $pendingTransaction['memo'],
                    'num' => $pendingTransaction['num'],
                    'mum' => $pendingTransaction['mum'],
                    'fee' => $pendingTransaction['fee'],
                    'addtime' => $pendingTransaction['addtime'],
                    'endtime' => time(),
                    'status' => 1
                ];
                $mo->table('codono_myzr')->add($depositData);

                // Credit the user's account
                $this->creditUserAccount($pendingTransaction['userid'], $pendingTransaction['coinname'], $pendingTransaction['mum']);

                // Notify the user about the successful deposit
                $this->addNotification($pendingTransaction['userid'], 'Deposit Approved', 'Your deposit has been approved and credited to your account.');
            } else {
                $pendingTransaction['aml_status'] = 2; // AML rejected
                $mo->table('codono_pending')->save($pendingTransaction);

                // Notify the user about the rejection
                $this->addNotification($pendingTransaction['userid'], 'Deposit Rejected', 'Your deposit has been rejected due to AML check failure.');
            }

            $mo->commit();
        } catch (\Exception $e) {
            $mo->rollback();
            throw $e;
        }
    }

    private function checkAmlStatus($txid)
    {
        // Implement your AML check logic here
        // Return true if approved, false otherwise
        return true;
    }

    private function creditUserAccount($userid, $coinname, $amount)
    {
        // Implement logic to credit the user's account
        $mo = new Model();
        $mo->table('codono_user_coin')->where(['userid' => $userid])->setInc($coinname, $amount);
    }
}
