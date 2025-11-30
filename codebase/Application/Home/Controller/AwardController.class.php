<?php
namespace Home\Controller;

class AwardController extends HomeController
{
    private int $dailyTrials;

    public function __construct()
    {
        parent::__construct();
        $this->dailyTrials = 12;
    }
    public function index()
    {
        $userid = userid();
        if ($userid) {
            $todayStart = strtotime('today');
            $awardCountToday = M('AwardAttempts')->where(['userid' => $userid, 'attempt_date' => date('Y-m-d')])->getField('attempts') ?: 0;
    
            if ($awardCountToday === 0) {
                list($awardNum, $uinfo) = $this->subAward($userid, $todayStart);
                $uinfo['awardNumToday'] = 0;
                M('User')->save($uinfo);
            }
    
            $curUserInfo = M('User')->where(['id' => $userid])->find();
            $uinfo = [
                'awardNumToday' => $curUserInfo['awardnumtoday'],
                'awardTotalToday' => $curUserInfo['awardtotaltoday']
            ];
        } else {
            $uinfo = ['awardNumToday' => 0, 'awardTotalToday' => 0];
        }
    
        // Fetch recent confirmed wins
        $recentWins = M('codono_user_award')
            ->alias('w')
            ->join('LEFT JOIN User u ON w.userid = u.id')
            ->field('u.username, w.awardname, w.addtime')
            ->where(['w.status' => 1])  // Only confirmed awards
            ->order('w.addtime DESC')
            ->limit(10)
            ->select();
    
        $this->assign('dailyTrials', $this->dailyTrials);
        $this->assign('uinfo', $uinfo);
        $this->assign('recentWins', $recentWins);
        $this->display();
    }
    
    public function v1index()
    {
        $userid = userid();
        if ($userid) {
            $todayStart = strtotime('today');
            $awardCountToday = M('AwardAttempts')->where(['userid' => $userid, 'attempt_date' => date('Y-m-d')])->getField('attempts') ?: 0;

            if ($awardCountToday === 0) {
                list($awardNum, $uinfo) = $this->subAward($userid, $todayStart);
                $uinfo['awardNumToday'] = 0;
                M('User')->save($uinfo);
            }

            $curUserInfo = M('User')->where(['id' => $userid])->find();
            $uinfo = [
                'awardNumToday' => $curUserInfo['awardnumtoday'],
                'awardTotalToday' => $curUserInfo['awardtotaltoday']
            ];
        } else {
            $uinfo = ['awardNumToday' => 0, 'awardTotalToday' => 0];
        }

        $this->assign('dailyTrials', $this->dailyTrials);
        $this->assign('uinfo', $uinfo);
        $this->display();
    }
    public function award()
    {
        $userid = userid();
        if (!$userid) {
            $this->outputAwardData(0, '', '', '', 10);
            return;
        }
    
        $eventEndTime = strtotime('2025-12-28 23:59:59');
        if (time() > $eventEndTime) {
            $this->outputAwardData(4, 'Event Ended');
            return;
        }
    
        $awardStatus = $this->evaluateAwardStatus($userid);
    
        if ($awardStatus === 3) {
            // Fetch available prizes with quantity > 0
            $availablePrizes = M('codono_awards')->where(['status' => 1, 'quantity' => ['gt', 0]])->select();
    
            if (empty($availablePrizes)) {
                $this->outputAwardData(0, 'No available prizes at this time');
                return;
            }
    
            // Randomly select a prize from the list
            $selectedPrize = $availablePrizes[array_rand($availablePrizes)];
    
            // Prepare and process the award transaction
            $awardInfo = $this->prepareAwardInfo($userid, $selectedPrize);
            $this->processAwardTransaction($userid, $awardInfo, $selectedPrize['id']);
        } else {
            $this->outputAwardData($awardStatus);
        }
    }
    
    private function processAwardTransaction($userid, $awardInfo, $prizeId)
    {
        $mo = M();
        $mo->startTrans();
        try {
            // Deduct prize quantity by 1
            $mo->table('codono_awards')->where(['id' => $prizeId])->setDec('quantity', 1);
    
            // Log the win in codono_user_award table with pending status (0)
            $awardInfo['status'] = 0;
            $awardId = $mo->table('codono_user_award')->add($awardInfo);
    
            // Mark the award as confirmed (status = 1) after a successful transaction
            $mo->table('codono_user_award')->where(['id' => $awardId])->save(['status' => 1, 'dealtime' => time()]);
    
            $mo->table('codono_user')->where(['id' => $userid])->save(['awardstatus' => 1]);
            $this->incrementAwardAttempts($userid);
    
            $mo->commit();
            $this->outputAwardData(3, 'Congratulations, you won [' . $awardInfo['awardname'] . ']', '', $awardInfo['awardid'], $awardInfo['image']);
        } catch (\Exception $e) {
            $mo->rollback();
            $this->outputAwardData(0);
            error_log("Award transaction failed for user {$userid}: " . $e->getMessage());
        }
    }
    
   


private function fetchPrize($prizeId)
{
    $prize = M('Awards')->where(['id' => $prizeId, 'status' => 1])->find();
    return $prize ?: ['id' => 0, 'name' => 'No prizes', 'image' => 'default.png'];
}


    private function prepareAwardInfo($userid, $prize)
    {
        return [
            'userid' => $userid,
            'awardname' => $prize['name'],
            'status' => 0,
            'addtime' => time(),
            'awardid' => $prize['id']
        ];
    }

    private function v1processAwardTransaction($userid, $awardInfo)
{
    $mo = M();
    $mo->startTrans();
    try {
        $mo->table('codono_user_award')->add($awardInfo);
        $mo->table('codono_user')->where(['id' => $userid])->save(['awardstatus' => 1]);
        $this->incrementAwardAttempts($userid);

        $mo->commit();
        $this->outputAwardData(3, 'Congratulations, you won [' . $awardInfo['awardname'] . ']', '', $awardInfo['awardid'], $awardInfo['image']);
    } catch (\Exception $e) {
        $mo->rollback();
        $this->outputAwardData(0);
        error_log("Award transaction failed for user {$userid}: " . $e->getMessage());  // Log error
    }
}


    private function evaluateAwardStatus($userid)
    {
        $todayStart = strtotime('today');
        list($awardNum, $uinfo) = $this->subAward($userid, $todayStart);

        M('User')->save($uinfo);

        return ($awardNum === 0) ? 1 : 3;
    }

    private function subAward($userid, $todayStart): array
    {
        $awardNum = floor(M('TradeLog')->where("(userid={$userid} OR peerid={$userid}) AND status=1 AND (addtime >= {$todayStart})")->sum('mum') / 300);
        $awardNum = min($awardNum, $this->dailyTrials);

        return [$awardNum, ['id' => $userid, 'awardTotalToday' => $awardNum]];
    }

    private function incrementAwardAttempts($userid)
    {
        $todayDate = date('Y-m-d');
        $attemptData = M('AwardAttempts')->where(['userid' => $userid, 'attempt_date' => $todayDate])->find();

        if ($attemptData) {
            M('AwardAttempts')->where(['userid' => $userid, 'attempt_date' => $todayDate])->setInc('attempts', 1);
        } else {
            M('AwardAttempts')->add(['userid' => $userid, 'attempt_date' => $todayDate, 'attempts' => 1]);
        }
    }

    private function outputAwardData($status, $name = '', $site = '', $id = '', $prizeId = 10, $image = 'default.png')
{
    $data = [
        'loginState' => $status,
        'prize_name' => $name,
        'prize_site' => $site,
        'prize_id' => $id ?: $prizeId,
        'image' => $image
    ];
    echo json_encode($data);
    exit;
}

}
