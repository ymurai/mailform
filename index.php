<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
include_once('../lib/dBug.php');
include_once('lib/Form.php');
// error_reporting(0);


$Form = new Form(array(
    'class' => 'mailform',
    'confirm' => true,
));

$Form->set('name')->type('text')->label('名前')->required(true);
$Form->set('pass')->type('password')->label('パスワード')->required(true);
$Form->set('value')->type('hidden')->value('値');
$Form->set('checkbox')->type('checkbox')->label('リスト')->choices(array('いち', 'に', 'さん'));
$Form->set('select')->type('select')->label('リスト')->choices(array('いち', 'に', 'さん'));
$Form->set('message')->type('textarea')->label('なにかメッセージ');

$Form->sendmail(array(
    'switch' => true,
    'to' => '〇〇あて',
    'to_mail' => 'y.murai.pc@gmail.com',
    'from' => 'サイトより',
    'from_mail' => 'y.murai.pc@gmail.com',
    'subject' => 'メール送信テスト',
    'body' => '
下記の入力がありました。

##name##
##pass##
##value##
##checkbox##
##select##
##message##
    ',
));
$Form->autoreply(array(
    'switch' => false,
    'to' => '〇〇あて',
    'from' => 'サイトより',
    'from_mail' => 'y.murai.pc@gmail.com',
    'subject' => 'メール送信テスト',
    'body' => '
下記の入力がありました。

##name##
##pass##
##value##
##checkbox##
##select##
##message##
',
));
// new dBug($Form->format);
// new dBug($_POST);

?>

<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
  <meta http-equiv="Content-Script-Type" content="text/javascript" />
  <title>mailform test</title>
</head>
<body>

  <div id="content">
    <h2>入力フォームの試作</h2>

    <?=$Form->html();?>

  </div>

</body>
</html>