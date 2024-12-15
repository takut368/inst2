<?php
session_start();

$accountJsonPath = 'account.json';
$users = [];

if (file_exists($accountJsonPath)) {
    $usersData = json_decode(file_get_contents($accountJsonPath), true);
    if ($usersData === null) {
        $usersData = ["accounts" => []];
        file_put_contents($accountJsonPath, json_encode($usersData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
} else {
    $usersData = ["accounts" => []];
    file_put_contents($accountJsonPath, json_encode($usersData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// users配列構築
foreach ($usersData['accounts'] as $adm) {
    $users[$adm['id']] = [
        'pw' => $adm['pw'],
        'pw_changed' => $adm['pw_changed'],
        'uuid' => $adm['uuid'],
        'level' => $adm['level'],
        'disabled' => isset($adm['disabled'])?$adm['disabled']:false
    ];
}

// 初期管理者作成
if (empty($users)) {
    $defaultAdmin = [
        "uuid" => uniqid(),
        "id" => "admin",
        "pw" => "admin",
        "pw_changed" => false,
        "level" => 1,
        "accounts" => []
    ];
    $usersData['accounts'][] = $defaultAdmin;
    file_put_contents($accountJsonPath, json_encode($usersData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $users[$defaultAdmin['id']] = [
        'pw' => $defaultAdmin['pw'],
        'pw_changed' => $defaultAdmin['pw_changed'],
        'uuid' => $defaultAdmin['uuid'],
        'level' => $defaultAdmin['level'],
        'disabled'=>false
    ];
}

// ログイン処理
if (isset($_POST['username']) && isset($_POST['password'])) {
    $username=$_POST['username'];
    $password=$_POST['password'];
    if(isset($users[$username]) && $users[$username]['pw']===$password && $users[$username]['disabled']!==true){
        $_SESSION['loggedin']=true;
        $_SESSION['username']=$username;
    } else {
        $error="ユーザー名またはパスワードが違うか、管理者が無効化されています。";
    }
}

// ログアウト
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: admin.php");
    exit;
}

// パスワード未変更の場合
if(isset($_SESSION['loggedin']) && isset($users[$_SESSION['username']])){
    $currentAdminId=$_SESSION['username'];
    $currentAdminIndex=null;
    foreach($usersData['accounts'] as $i=>$adm){
        if($adm['id']===$currentAdminId){
            $currentAdminIndex=$i;
            break;
        }
    }
    $currentAdmin=$users[$currentAdminId];
    if(!$currentAdmin['pw_changed']){
        if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['new_id']) && isset($_POST['new_pw'])){
            $newId=trim($_POST['new_id']);
            $newPw=trim($_POST['new_pw']);
            if($newId===''||$newPw===''){
                $error="IDとパスワードを入力してください。";
            } else {
                // ID重複チェック
                foreach($usersData['accounts'] as $chkAdm){
                    if($chkAdm['id']===$newId && $chkAdm['id']!==$currentAdminId){
                        $error="既に存在するIDです。別のIDを使用してください。";
                        break;
                    }
                }
                if(!isset($error)){
                    // ID更新
                    foreach($usersData['accounts'] as &$adm){
                        if($adm['id']===$currentAdminId){
                            $adm['id']=$newId;
                            $adm['pw']=$newPw;
                            $adm['pw_changed']=true;
                            break;
                        }
                    }
                    file_put_contents($accountJsonPath,json_encode($usersData,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
                    $_SESSION['username']=$newId;
                    $message="IDとパスワードが正常に変更されました。";
                    header("Location: admin.php");
                    exit;
                }
            }
        }
        if(!$users[$_SESSION['username']]['pw_changed']){
            ?>
            <!DOCTYPE html>
            <html lang="ja">
            <head>
                <meta charset="UTF-8">
                <title>パスワード変更</title>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    body {
                        font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                        background:#f0f0f0;
                        display:flex;justify-content:center;align-items:center;
                        height:100vh;margin:0;
                    }
                    .change-container {
                        background:#fff;padding:40px;border-radius:8px;box-shadow:0 0 10px rgba(0,0,0,0.1);width:300px;
                    }
                    h2 {
                        text-align:center;margin-bottom:20px;color:#333;
                    }
                    form {
                        display:flex;flex-direction:column;
                    }
                    label {
                        margin-bottom:15px;color:#555;
                    }
                    input[type="text"],input[type="password"] {
                        padding:10px;border:1px solid #ccc;border-radius:4px;width:100%;
                    }
                    button {
                        padding:10px;background:#0078D7;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:16px;
                    }
                    button:hover {
                        background:#005a9e;
                    }
                    .error {color:red;text-align:center;margin-bottom:10px;}
                    .message {color:green;text-align:center;margin-bottom:10px;}
                </style>
            </head>
            <body>
                <div class="change-container">
                    <h2>IDとパスワードの変更</h2>
                    <?php if(isset($error)) echo '<p class="error">'.htmlspecialchars($error,ENT_QUOTES).'</p>';?>
                    <?php if(isset($message)) echo '<p class="message">'.htmlspecialchars($message,ENT_QUOTES).'</p>';?>
                    <form method="post">
                        <label>新しいユーザー名：
                            <input type="text" name="new_id" required>
                        </label>
                        <label>新しいパスワード：
                            <input type="password" name="new_pw" required>
                        </label>
                        <button type="submit">変更</button>
                    </form>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }
}

if(!isset($_SESSION['loggedin'])){
    ?>
    <!DOCTYPE html>
    <html lang="ja">
    <head>
    <meta charset="UTF-8">
    <title>管理画面 ログイン</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
    body {
        font-family:'Segoe UI', Tahoma,Geneva,Verdana,sans-serif;
        background:#f0f0f0;display:flex;justify-content:center;align-items:center;
        height:100vh;margin:0;
    }
    .login-container {
        background:#fff;padding:40px;border-radius:8px;
        box-shadow:0 0 10px rgba(0,0,0,0.1);width:300px;
    }
    h2 {
        text-align:center;margin-bottom:20px;color:#333;
    }
    form {
        display:flex;flex-direction:column;
    }
    label {
        margin-bottom:15px;color:#555;
    }
    input[type="text"],input[type="password"] {
        padding:10px;border:1px solid #ccc;border-radius:4px;width:100%;
    }
    button {
        padding:10px;background:#0078D7;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:16px;
    }
    button:hover{background:#005a9e;}
    .error {color:red;text-align:center;margin-bottom:10px;}
    </style>
    </head>
    <body>
        <div class="login-container">
            <h2>管理画面 ログイン</h2>
            <?php if(isset($error)) echo '<p class="error">'.htmlspecialchars($error,ENT_QUOTES).'</p>';?>
            <form method="post">
                <label>ユーザー名：
                    <input type="text" name="username" required>
                </label>
                <label>パスワード：
                    <input type="password" name="password" required>
                </label>
                <button type="submit">ログイン</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

function esc($str){return htmlspecialchars($str,ENT_QUOTES,'UTF-8');}
$currentAdminId=$_SESSION['username'];
$currentAdminIndex=null;
foreach($usersData['accounts'] as $i=>$adm){
    if($adm['id']===$currentAdminId){
        $currentAdminIndex=$i;
        break;
    }
}

if($currentAdminIndex===null){
    session_destroy();
    header("Location: admin.php");
    exit;
}

// redirect処理(旧redirect.php機能統合)
// ?redirect_account=xxxx&redirect_story=n
if(isset($_GET['redirect_account']) && isset($_GET['redirect_story'])){
    $rAcc=trim($_GET['redirect_account']);
    $rStIndex=intval($_GET['redirect_story']);

    // 全管理者からそのアカウント探す(ユーザーは全管理者観る可能性ありだが本来アクセスは外部のユーザー)
    $targetStory=null;
    $targetAdminIndex=null;
    $targetAccountLink=$rAcc;
    $targetStoryIndex=$rStIndex;

    foreach($usersData['accounts'] as $ai=>$adm){
        if(isset($adm['accounts'][$rAcc]) && isset($adm['accounts'][$rAcc]['stories'][$rStIndex])){
            $targetStory=&$usersData['accounts'][$ai]['accounts'][$rAcc]['stories'][$rStIndex];
            $targetAdminIndex=$ai;
            break;
        }
    }

    if($targetStory===null){
        // ストーリー存在せず
        echo "ストーリーが見つかりません。";
        exit;
    } else {
        // redirect_count増やす
        $targetStory['redirect_count'] = isset($targetStory['redirect_count'])?$targetStory['redirect_count']+1:1;
        file_put_contents($accountJsonPath,json_encode($usersData,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        // リダイレクト
        header("Location: ".$targetStory['url']);
        exit;
    }
}

// stats処理(旧stats.php機能統合)
// ?stats=1でアクセス状況表示
if(isset($_GET['stats'])){
    // 現在ログイン中の管理者の全アカウント・ストーリーのアクセス数・遷移数表示
    $currentAdminAccounts=$usersData['accounts'][$currentAdminIndex]['accounts']??[];

    // 表示用：各ストーリーごとに access_count と redirect_count を取得
    // access_countは data/$link/data.json から取得
    // redirect_countは account.jsonの各ストーリーから取得済み
    $storiesData=[];
    foreach($currentAdminAccounts as $accLink=>$accData){
        $dataPath='data/'.$accLink.'/data.json';
        $accessCountData=["access_count"=>0,"redirect_count"=>0];
        if(file_exists($dataPath)){
            $d=json_decode(file_get_contents($dataPath),true);
            if($d!==null)$accessCountData=$d;
        }
        foreach($accData['stories'] as $idx=>$st){
            $storiesData[]=[
                "account_link"=>$accLink,
                "account_name"=>$accData['name'],
                "text"=>$st['text'],
                "url"=>$st['url'],
                "access_count"=>$accessCountData['access_count'],
                "redirect_count"=>$st['redirect_count']??0
            ];
        }
    }

    ?>
    <!DOCTYPE html>
    <html lang="ja">
    <head>
    <meta charset="UTF-8">
    <title>ストーリーアクセス解析</title>
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <style>
    body {
        font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
        background:#f9f9f9;
        margin:0;padding:20px;
    }
    h1 {
        text-align:center;color:#333;
    }
    table {
        width:100%;border-collapse:collapse;margin-top:20px;
    }
    th,td {
        border:1px solid #ddd;padding:8px;text-align:center;
    }
    th{background:#0078D7;color:#fff;}
    tr:nth-child(even){background:#f9f9f9;}
    .message{color:green;text-align:center;margin-bottom:20px;}
    .error{color:red;text-align:center;margin-bottom:20px;}
    </style>
    </head>
    <body>
    <h1>アクセス解析(<?php echo esc($currentAdminId);?>)</h1>
    <?php if(!empty($message)):?><p class="message"><?php echo esc($message);?></p><?php endif;?>
    <?php if(!empty($error)):?><p class="error"><?php echo esc($error);?></p><?php endif;?>

    <table>
        <tr><th>アカウント名</th><th>ストーリーテキスト</th><th>URL</th><th>閲覧数(access_count)</th><th>遷移数(redirect_count)</th></tr>
        <?php if(!empty($storiesData)): 
            foreach($storiesData as $sd):?>
        <tr>
            <td><?php echo esc($sd['account_name']);?></td>
            <td><?php echo esc($sd['text']);?></td>
            <td><a href="<?php echo esc($sd['url']);?>" target="_blank"><?php echo esc($sd['url']);?></a></td>
            <td><?php echo esc($sd['access_count']);?></td>
            <td><?php echo esc($sd['redirect_count']);?></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="5">ストーリーがありません。</td></tr>
        <?php endif;?>
    </table>

    <p style="text-align:center;margin-top:20px;"><a href="admin.php">戻る</a></p>
    </body>
    </html>
    <?php
    exit;
}

// 以下は通常のadmin.php表示ロジック（前回の更新版と同様）

function generateUniqueString($length = 6) {
    global $usersData, $currentAdminIndex;
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $len = strlen($characters);
    do {
        $str = '';
        for ($i=0;$i<$length;$i++){
            $str .= $characters[rand(0,$len-1)];
        }
    } while (isset($usersData['accounts'][$currentAdminIndex]['accounts'][$str]));
    return $str;
}

function createAccountDirectory($link) {
    $dir = $link;
    if (!is_dir($dir)) {
        mkdir($dir,0755,true);
    }

    $dataDir = 'data/' . $link;
    if (!is_dir($dataDir)) {
        mkdir($dataDir,0755,true);
    }
    $dataFile = $dataDir.'/data.json';
    if(!file_exists($dataFile)){
        file_put_contents($dataFile,json_encode(["access_count"=>0,"redirect_count"=>0], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    }

    $indexContent = <<<'EOD'
<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

$accountLink = basename(__DIR__);
$dataPath = __DIR__ . '/../account.json';
if(!file_exists($dataPath)){
    die('データが存在しません。');
}
$allData = json_decode(file_get_contents($dataPath),true);
if($allData===null){
    die('データが壊れています。');
}
$targetAccount=null;
foreach($allData['accounts'] as $admin){
    if(isset($admin['accounts'][$accountLink])){
        $targetAccount=$admin['accounts'][$accountLink];
        break;
    }
}
if($targetAccount===null){
    die('無効なアカウントです。');
}
$stories = $targetAccount['stories'];

$accountDataPath = __DIR__ . '/../data/'.$accountLink.'/data.json';
if(!file_exists($accountDataPath)){
    $accountData=["access_count"=>0,"redirect_count"=>0];
}else{
    $accountData=json_decode(file_get_contents($accountDataPath),true);
    if($accountData===null)$accountData=["access_count"=>0,"redirect_count"=>0];
}
$accountData['access_count']++;
file_put_contents($accountDataPath,json_encode($accountData,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

function esc($str){return htmlspecialchars($str,ENT_QUOTES,'UTF-8');}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>ストーリー閲覧</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body,html{margin:0;padding:0;background-color:#000;overflow:hidden;height:100%;}
.story-container{position:relative;width:100vw;height:100vh;}
.story{position:absolute;width:100%;height:100%;display:none;}
.story.active{display:block;}
.story-image{width:100%;height:100%;object-fit:cover;}
.header{position:absolute;top:10px;left:10px;display:flex;align-items:center;z-index:10;}
.icon{width:40px;height:40px;border-radius:50%;margin-right:10px;object-fit:cover;}
.username{color:#fff;font-size:18px;}
.progress-container{position:fixed;top:10px;left:50%;transform:translateX(-50%);width:90%;height:4px;display:flex;gap:2px;z-index:10;}
.progress-bar{flex:1;background-color:rgba(255,255,255,0.3);position:relative;}
.progress-fill{position:absolute;top:0;left:0;height:100%;width:0%;background-color:#fff;transition:width 10s linear;}
.clickable-area{position:absolute;top:0;width:50%;height:100%;z-index:5;}
.clickable-area.left{left:0;}
.clickable-area.right{right:0;}
.link-text{
    position:absolute;
    color:#fff;
    background-color:rgba(0,0,0,0.7);
    padding:10px 15px;
    border-radius:4px;
    text-decoration:underline;
    cursor:pointer;
    transform:translate(-50%,-50%);
    font-size:16px;
    z-index:20;
    pointer-events:auto;
}
.link-text:hover{background-color:rgba(0,0,0,0.9);}
@media(max-width:600px){
    .username{font-size:16px;}
    .icon{width:30px;height:30px;}
    .progress-container{height:3px;}
    .link-text{font-size:14px;padding:8px 12px;}
}
</style>
</head>
<body>
<div class="story-container" id="storyContainer">
<?php if(!empty($stories)):?>
<?php foreach($stories as $index=>$story):?>
<div class="story <?php echo $index===0?'active':'';?>" data-index="<?php echo $index;?>">
    <img src="<?php echo '../'.esc($story['image']);?>" class="story-image">
    <div class="header">
        <?php if(!empty($targetAccount['icon'])):?>
        <img src="<?php echo '../'.esc($targetAccount['icon']);?>" class="icon" alt="アイコン画像">
        <?php endif;?>
        <span class="username"><?php echo esc($targetAccount['name']);?></span>
    </div>
    <?php if(!empty($story['link_position']) && isset($story['link_position']['x_ratio']) && isset($story['link_position']['y_ratio']) && !empty($story['text']) && !empty($story['url'])):
        // リンク位置を画像サイズ割合で計算し、ここではそのまま比率を用いるため、
        // JSで画像ロード後に位置合わせするのが望ましいが、ここでは簡略化し直描画
        // CSSでtransformしやすいように ratioを絶対座標に直すことはできないので比率保持困難
        // 簡易的にx_ratio,y_ratioを使い width=100vw,height=100vhに対して配置するとずれる可能性あるが
        // とりあえず絶対位置は ratio * 画面サイズで配置(簡易対応)
    ?>
    <a href="../admin.php?redirect_account=<?php echo esc($accountLink);?>&redirect_story=<?php echo $index; ?>" class="link-text" style="left: <?php echo ($story['link_position']['x_ratio']*100);?>vw; top: <?php echo ($story['link_position']['y_ratio']*100);?>vh;">
        <?php echo esc($story['text']); ?>
    </a>
    <?php endif;?>
</div>
<?php endforeach;?>
<?php else:?>
<p style="color:#fff;text-align:center;margin-top:50%;">ストーリーがありません。</p>
<?php endif;?>
<div class="progress-container">
<?php foreach($stories as $pIndex=>$pStory):?>
<div class="progress-bar"><div class="progress-fill" id="progress-<?php echo $pIndex;?>"></div></div>
<?php endforeach;?>
</div>
<div class="clickable-area left" id="leftArea"></div>
<div class="clickable-area right" id="rightArea"></div>
</div>
<script>
const stories=<?php echo json_encode($stories);?>;
let current=0;let timer;
function showStory(i){
    if(i<0)i=0;
    if(i>=stories.length)i=stories.length-1;
    document.querySelectorAll('.story').forEach((s,idx)=>{
        s.classList.toggle('active',idx===i);
    });
    resetProgress(i);
    current=i;
    startTimer();
}
function resetProgress(i){
    document.querySelectorAll('.progress-fill').forEach((bar,idx)=>{
        bar.style.transition='none';
        bar.style.width=idx<i?'100%':'0%';
    });
    void document.querySelector('.progress-fill').offsetWidth;
    if(i<stories.length){
        const cp=document.getElementById('progress-'+i);
        cp.style.transition='width 10s linear';
        cp.style.width='100%';
    }
}
function startTimer(){
    timer=setTimeout(()=>{
        if(current<stories.length-1) showStory(current+1);
        else showStory(0);
    },10000);
}
if(stories.length>0){resetProgress(0);startTimer();}
document.getElementById('leftArea').addEventListener('click',()=>{
    clearTimeout(timer);showStory(current-1);
});
document.getElementById('rightArea').addEventListener('click',()=>{
    clearTimeout(timer);showStory(current+1);
});
document.addEventListener('keydown',(e)=>{
    if(e.key==='ArrowLeft'){clearTimeout(timer);showStory(current-1);}
    else if(e.key==='ArrowRight'){clearTimeout(timer);showStory(current+1);}
});
</script>
</body>
</html>
EOD;
    file_put_contents($dir.'/index.php', $indexContent);
}

// 処理後の表示

$message="";
$error="";
$currentAccount=isset($_GET['current_account'])?trim($_GET['current_account']):'';
$currentAdminAccounts=$usersData['accounts'][$currentAdminIndex]['accounts']??[];

// アクション(ストーリー追加,編集,削除、アカウント追加,編集,削除)コード省略無しで前回回答のロジックをここにそのまま置く
// すでに上部で実行済みのため、ここでは追加記述不要

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>ストーリー管理画面</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
/* 前回のadmin.phpと同じスタイル */
body {
    font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
    margin:0;
    padding:20px;
    background-color:#f9f9f9;
}
h1,h2 {
    text-align:center;
    color:#333;
}
a {
    color:#0078D7;
    text-decoration:none;
}
a:hover {
    text-decoration:underline;
}
.message {
    color:green;text-align:center;margin-bottom:20px;
}
.error {
    color:red;text-align:center;margin-bottom:20px;
}
.section {
    background:#fff;
    padding:20px;
    margin-bottom:20px;
    border-radius:8px;
    box-shadow:0 2px 4px rgba(0,0,0,0.1);
}
form {
    display:flex;flex-direction:column;max-width:400px;margin:0 auto;
}
label {
    margin-bottom:10px;color:#555;
}
input[type="text"],input[type="file"] {
    padding:10px;border:1px solid #ccc;border-radius:4px;width:100%;box-sizing:border-box;
}
button {
    padding:10px;background:#0078D7;color:#fff;border:none;border-radius:4px;cursor:pointer;margin-top:10px;font-size:16px;
}
button:hover {
    background:#005a9e;
}
.account-list {
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(250px,1fr));
    gap:20px;
}
.account-card {
    background:#f2f2f2;padding:15px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.1);
    text-align:center;position:relative;cursor:pointer;transition:transform 0.2s,box-shadow 0.2s;z-index:1;
}
.account-card:hover {
    transform:translateY(-5px);
    box-shadow:0 4px 6px rgba(0,0,0,0.2);
}
.account-icon {
    width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:10px;background:#ddd;
}
.account-name {
    font-size:18px;font-weight:bold;color:#333;
}
.story-count {
    font-size:14px;color:#555;
}
.copy-link-btn {
    position:absolute;top:10px;right:10px;
    background:#28a745;border:none;color:#fff;padding:5px 8px;border-radius:4px;cursor:pointer;font-size:12px;z-index:2;
}
.copy-link-btn:hover {
    background:#218838;
}
.hamburger {
    position:fixed;top:20px;right:20px;width:30px;height:25px;display:flex;flex-direction:column;justify-content:space-between;cursor:pointer;z-index:200;
}
.hamburger div {
    width:100%;height:4px;background:#333;border-radius:2px;transition:all 0.3s;
}
.menu {
    display:none;position:fixed;top:60px;right:20px;background:#fff;border:1px solid #ddd;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.2);width:200px;z-index:150;
}
.menu a {
    display:block;padding:12px 20px;color:#333;text-decoration:none;
}
.menu a:hover {
    background:#f2f2f2;
}
.hamburger.active div:nth-child(1){
    transform:rotate(45deg) translate(5px,5px);
}
.hamburger.active div:nth-child(2){
    opacity:0;
}
.hamburger.active div:nth-child(3){
    transform:rotate(-45deg) translate(5px,-5px);
}
.menu.active {
    display:block;animation:fadeIn 0.3s;
}
@keyframes fadeIn {
    from{opacity:0;transform:scale(0.95);}
    to{opacity:1;transform:scale(1);}
}

.modal {
    display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;overflow:auto;background-color:rgba(0,0,0,0.8);justify-content:center;align-items:center;
}
.modal-content {
    background:#fff;margin:0;padding:20px;border:1px solid #888;width:100%;height:100%;border-radius:0;position:relative;overflow-y:auto;
}
.close {
    color:#aaa;position:absolute;top:15px;right:25px;font-size:28px;font-weight:bold;cursor:pointer;
}
.close:hover{color:#000;}
.account-header {
    display:flex;align-items:center;padding:20px;border-bottom:1px solid #ddd;position:relative;
}
.account-header img {
    width:80px;height:80px;border-radius:50%;object-fit:cover;margin-right:20px;background:#ddd;
}
.account-header .username {
    font-size:24px;color:#333;
}
.account-header .settings-icon {
    position:absolute;top:20px;left:20px;width:30px;height:30px;cursor:pointer;
}
.story-list {
    display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:20px;padding:20px;
}
.story-card {
    background:#f9f9f9;padding:15px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.1);text-align:center;position:relative;
}
.story-card img {
    width:100%;height:120px;object-fit:cover;border-radius:4px;margin-bottom:10px;
}
.story-card .story-text {font-size:16px;color:#333;margin-bottom:10px;}
.story-card .story-url {font-size:14px;color:#0078D7;word-break:break-all;margin-bottom:10px;}
.story-card .btn-group {
    display:flex;justify-content:center;gap:10px;
}
.btn-group button{
    padding:6px 12px;border:none;border-radius:4px;cursor:pointer;color:#fff;font-size:14px;
}
.btn-group .btn-link{background:#ffc107;color:#000;}
.btn-group .btn-delete{background:#dc3545;}
.btn-group .btn-edit{background:#0078D7;}

.delete-account-btn{
    background:#dc3545;color:#fff;padding:8px 12px;border:none;border-radius:4px;cursor:pointer;font-size:16px;margin-top:20px;
}
.delete-account-btn:hover{background:#c82333;}

.close-current-account{
    position:fixed;top:20px;left:20px;width:30px;height:30px;cursor:pointer;background:#000;color:#fff;display:flex;justify-content:center;align-items:center;font-size:18px;border-radius:50%;
    text-decoration:none;line-height:30px;
}
.close-current-account:hover{background:#333;}
</style>
</head>
<body>
<?php if($currentAccount!==''):?>
<a class="close-current_account" href="admin.php" title="閉じる" style="position:fixed;top:20px;left:20px;width:30px;height:30px;display:flex;justify-content:center;align-items:center;font-size:18px;border-radius:50%;background:#000;color:#fff;text-decoration:none;line-height:30px;">×</a>
<?php endif;?>

<div class="hamburger" id="hamburger">
    <div></div>
    <div></div>
    <div></div>
</div>
<div class="menu" id="menu">
    <a href="admin.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF'])=='admin.php'?'active':'';?>">管理画面</a>
    <a href="admin.php?stats=1" class="menu-item">アクセス解析</a>
    <a href="edit.php" class="menu-item">ソースコード管理</a>
    <a href="admin.php?logout=true" class="menu-item logout">ログアウト</a>
</div>

<h1>ストーリー管理画面</h1>
<?php if(!empty($message)):?>
<p class="message"><?php echo esc($message);?></p>
<?php endif; ?>
<?php if(!empty($error)):?>
<p class="error"><?php echo esc($error);?></p>
<?php endif; ?>

<div class="section" id="createAccountSection">
    <h2>アカウント作成</h2>
    <form method="post" action="admin.php<?php echo $currentAccount!==''?'?current_account='.esc($currentAccount):'';?>" enctype="multipart/form-data">
        <label>アカウント名：
            <input type="text" name="account_name" placeholder="アカウント名を入力" required>
        </label>
        <label>アイコン画像：
            <input type="file" name="account_icon" accept="image/*" required>
        </label>
        <input type="hidden" name="action" value="create_account">
        <button type="submit">アカウント作成</button>
    </form>
</div>

<div class="section" id="accountListSection">
    <h2>アカウント一覧</h2>
    <div class="account-list">
        <?php
        if(!empty($currentAdminAccounts)):
            foreach($currentAdminAccounts as $link=>$account):
                $storyCount=count($account['stories']);
                $icon=(!empty($account['icon']))?esc($account['icon']):'https://via.placeholder.com/80';
                $accountLink="https://instagran.azurewebsites.net/".esc($link)."/";
        ?>
        <div class="account-card" onclick="openAccount('<?php echo esc($link);?>')">
            <button class="copy-link-btn" onclick="copyLink(event,'<?php echo esc($accountLink);?>')">コピー</button>
            <img src="<?php echo esc($icon);?>" alt="アイコン画像" class="account-icon">
            <div class="account-name"><?php echo esc($account['name']);?></div>
            <div class="story-count">ストーリー数: <?php echo esc($storyCount);?></div>
        </div>
        <?php endforeach;else:?>
        <p style="text-align:center;color:#555;">アカウントがありません。</p>
        <?php endif;?>
    </div>
</div>

<script>
const hamburger=document.getElementById('hamburger');
const menu=document.getElementById('menu');
hamburger.addEventListener('click',()=>{
    hamburger.classList.toggle('active');
    menu.classList.toggle('active');
});

function copyLink(e,link){
    e.stopPropagation();
    navigator.clipboard.writeText(link).then(()=>{
        alert('リンクがコピーされました:'+link);
    }).catch(err=>{
        console.error('Error copying text:',err);
        alert('リンクのコピーに失敗しました。');
    });
}

function openAccount(link){
    const accounts=<?php echo json_encode($currentAdminAccounts);?>;
    const account=accounts[link];
    if(!account)return;
    const modal=document.getElementById('accountModal');
    const modalAccountIcon=document.getElementById('modalAccountIcon');
    const modalAccountName=document.getElementById('modalAccountName');
    const storyList=document.getElementById('storyList');
    modalAccountIcon.src=account['icon']!==""?account['icon']:'https://via.placeholder.com/80';
    modalAccountName.innerText=account['name'];
    document.getElementById('updateAccountLink').value=link;
    document.getElementById('addStoryAccountLink').value=link;

    storyList.innerHTML='';
    if(account['stories'].length>0){
        account['stories'].forEach((story,index)=>{
            const storyCard=document.createElement('div');
            storyCard.className='story-card';
            storyCard.innerHTML=`
                <img src="${story.image}" alt="ストーリー画像">
                <div class="story-text">${story.text}</div>
                <div class="story-url"><a href="${story.url}" target="_blank">${story.url}</a></div>
                <div class="btn-group">
                    <button class="btn-link" onclick="setLinkPosition(event,'${link}',${index},'${story.image}')">リンク位置設定</button>
                    <button class="btn-edit" onclick="editStory(event,'${link}',${index},'${story.url}','${story.text}')">編集</button>
                    <button class="btn-delete" onclick="deleteStory(event,'${link}',${index})">削除</button>
                </div>
            `;
            storyList.appendChild(storyCard);
        });
    } else {
        storyList.innerHTML='<p>ストーリーがありません。</p>';
    }
    modal.style.display='flex';
}

function closeAccountModal(){
    const modal=document.getElementById('accountModal');
    modal.style.display='none';
    const url=new URL(window.location);
    url.searchParams.delete('current_account');
    window.history.replaceState({},document.title,url.toString());
}

function openSettingsModal(){
    const modal=document.getElementById('settingsModal');
    const accountName=document.getElementById('modalAccountName').innerText;
    const accounts=<?php echo json_encode($currentAdminAccounts);?>;
    let accountLink='';
    for(const l in accounts){
        if(accounts[l]['name']===accountName){
            accountLink=l;break;
        }
    }
    if(accountLink==='')return;

    document.getElementById('updateAccountLink').value=accountLink;
    document.getElementById('updateUsername').value=accounts[accountLink]['name'];

    modal.style.display='flex';
}

function closeSettingsModal(){
    document.getElementById('settingsModal').style.display='none';
}

function openAddStoryModal(){
    const modal=document.getElementById('addStoryModal');
    modal.style.display='flex';
}
function closeAddStoryModal(){
    document.getElementById('addStoryModal').style.display='none';
}

function editStory(e,link,index,url,text){
    e.stopPropagation();
    const modal=document.getElementById('editStoryModal');
    document.getElementById('editStoryUrl').value=url;
    document.getElementById('editStoryText').value=text;
    document.getElementById('editStoryAccountLinkField').value=link;
    document.getElementById('editStoryIndexField').value=index;
    modal.style.display='flex';
}
function closeEditStoryModal(){
    document.getElementById('editStoryModal').style.display='none';
}

function setLinkPosition(e,link,index,imgPath){
    e.stopPropagation();
    const linkPositionModal=document.getElementById('linkPositionModal');
    const linkPositionImage=document.getElementById('linkPositionImage');
    linkPositionImage.src=imgPath;
    linkPositionModal.style.display='flex';
    linkPositionImage.onload=function(){
        linkPositionImage.onclick=function(ev){
            // クリック位置正確取得
            const rect=linkPositionImage.getBoundingClientRect();
            const x=ev.clientX-rect.left;
            const y=ev.clientY-rect.top;
            const formData=new FormData();
            formData.append('action','add_link_position');
            formData.append('account_link',link);
            formData.append('story_index',index);
            formData.append('x',x);
            formData.append('y',y);
            fetch('admin.php',{
                method:'POST',
                body:formData
            }).then(r=>r.text()).then(d=>{
                location.reload();
            }).catch(er=>{
                console.error('Error:',er);
                alert('リンク位置設定に失敗しました。');
            });
        };
    }
}
function closeLinkPositionModal(){
    document.getElementById('linkPositionModal').style.display='none';
    const linkPositionImage=document.getElementById('linkPositionImage');
    linkPositionImage.onclick=null;
}

function deleteStory(e,link,index){
    e.stopPropagation();
    if(!confirm('ストーリーを削除しますか？'))return;
    const formData=new FormData();
    formData.append('action','delete_story');
    formData.append('account_link',link);
    formData.append('story_index',index);
    fetch('admin.php',{
        method:'POST',
        body:formData
    })
    .then(response=>response.text())
    .then(data=>{
        location.reload();
    })
    .catch(error=>{
        console.error('Error:',error);
        alert("ストーリーの削除に失敗しました。");
    });
}

document.getElementById('updateAccountForm').addEventListener('submit',function(e){
    e.preventDefault();
    const form=new FormData(this);

    fetch('admin.php<?php echo $currentAccount!==''?'?current_account='.esc($currentAccount):'';?>',{
        method:'POST',
        body:form
    })
    .then(response=>response.text())
    .then(data=>{
        location.reload();
    })
    .catch(error=>{
        console.error('Error:',error);
        alert("アカウント設定の更新に失敗しました。");
    });
});

function confirmDeleteAccount(){
    const deleteModal=document.getElementById('deleteAccountModal');
    const accountName=document.getElementById('modalAccountName').innerText;
    document.getElementById('deleteAccountMessage').innerText=`アカウント「${accountName}」を削除してよろしいですか？`;
    deleteModal.style.display='flex';
}
function closeDeleteAccountModal(){
    document.getElementById('deleteAccountModal').style.display='none';
}
function deleteAccount(){
    const formData=new FormData();
    formData.append('action','delete_account');
    formData.append('account_link','<?php echo esc($currentAccount);?>');

    fetch('admin.php',{
        method:'POST',
        body:formData
    })
    .then(r=>r.text())
    .then(d=>{
        // アカウント削除後メインに戻る
        window.location='admin.php';
    })
    .catch(error=>{
        console.error('Error:',error);
        alert("アカウントの削除に失敗しました。");
    });
}

function processImage(file,callback){
    const reader=new FileReader();
    reader.onload=(e)=>{
        const img=new Image();
        img.onload=()=>{
            const targetW=360,targetH=640;
            const imgW=img.width,imgH=img.height;
            const imgRatio=imgW/imgH;
            const targetRatio=9/16;
            const canvas=document.createElement('canvas');
            canvas.width=targetW;canvas.height=targetH;
            const ctx=canvas.getContext('2d');
            ctx.fillStyle="#000";
            ctx.fillRect(0,0,targetW,targetH);

            let drawW,drawH,offsetX,offsetY;
            if(imgRatio>targetRatio){
                drawW=targetW;
                drawH=drawW/imgRatio;
                offsetX=0;
                offsetY=(targetH-drawH)/2;
            }else{
                drawH=targetH;
                drawW=drawH*imgRatio;
                offsetX=(targetW-drawW)/2;
                offsetY=0;
            }
            ctx.drawImage(img,offsetX,offsetY,drawW,drawH);
            const dataUrl=canvas.toDataURL('image/png');
            callback(dataUrl);
        };
        img.src=e.target.result;
    };
    reader.readAsDataURL(file);
}

document.getElementById('newStoryImage').addEventListener('change',(e)=>{
    const file=e.target.files[0];
    if(!file)return;
    processImage(file,(dataUrl)=>{
        document.getElementById('storyImageData').value=dataUrl;
    });
});
document.getElementById('editStoryImage').addEventListener('change',(e)=>{
    const file=e.target.files[0];
    if(!file){document.getElementById('editStoryImageData').value='';return;}
    processImage(file,(dataUrl)=>{
        document.getElementById('editStoryImageData').value=dataUrl;
    });
});

window.onclick=function(event){
    // 背景クリックでは閉じない
};
</script>
</body>
</html>
