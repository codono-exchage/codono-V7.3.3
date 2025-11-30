<?php
if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
    $address= $_SERVER["HTTP_CF_CONNECTING_IP"];
}else{
    $address=$_SERVER['REMOTE_ADDR'];
}

/*A quick solution to SHOW XML based Error */
header("Content-type: text/xml");
$requestid=strtoupper(substr(md5(uniqid(rand(), true)),0,12));
$hostid=base64_encode(md5("Codono".date('DYMHis')));
?>

<Error><Code>AccessDenied</Code><IPAddress><?php echo $address;?></IPAddress><RequestedURI><?php echo $_SERVER['REQUEST_URI']; ?></RequestedURI><Message>Access Denied</Message><RequestId><?php echo $requestid;?></RequestId><HostId><?php echo $hostid;?> </HostId></Error>