<?php
// sys_overseer_portal.php
// 認証なし（試用版）
// 修正済み:
// - 全管理者一覧にPW, ストーリー数追加
// - 詳細ボタン追加、管理者詳細画面で停止、削除、レベル編集、アカウント一覧、ストーリー一覧、アカウント削除、ストーリー削除
// - 全ストーリー一覧のストーリーカードクリックで詳細画面表示（削除、閲覧、URL、テキスト、アクセス、リダイレクト表示）

$accountJsonPath = 'account.json';
$systemJsonPath = 'system.json';

if (file_exists($accountJsonPath)) {
    $usersData = json_decode(file_get_contents($accountJsonPath), true);
    if ($usersData===null) {
        $usersData=["accounts"=>[]];
        file_put_contents($accountJsonPath,json_encode($usersData,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    }
} else {
    $usersData=["accounts"=>[]];
    file_put_contents($accountJsonPath,json_encode($usersData,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
}

if (file_exists($systemJsonPath)) {
    $systemData = json_decode(file_get_contents($systemJsonPath), true);
    if ($systemData===null) {
        $systemData=["admin_levels"=>[]];
        file_put_contents($systemJsonPath,json_encode($systemData,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    }
} else {
    $systemData=["admin_levels"=>[]];
    file_put_contents($systemJsonPath,json_encode($systemData,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
}

function esc($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}

$message="";
$error="";

$action=isset($_POST['action'])?$_POST['action']:'';
$viewAdmin=isset($_GET['admin'])?trim($_GET['admin']):'';
$viewStory=isset($_GET['story_admin'])?trim($_GET['story_admin']):'';
$viewStoryAcc=isset($_GET['story_acc'])?trim($_GET['story_acc']):'';
$viewStoryIndex=isset($_GET['story_index'])?intval($_GET['story_index']):-1;

// 管理者停止
if($action==='disable_admin'){
    $adminId=isset($_POST['admin_id'])?trim($_POST['admin_id']):'';
    if($adminId===''){
        $error="管理者IDが無効です。";
    } else {
        $found=false;
        foreach($usersData['accounts'] as &$adm){
            if($adm['id']===$adminId){
                $adm['disabled']=true;
                file_put_contents($accountJsonPath,json_encode($usersData,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
                $message="管理者(ID:$adminId)を停止しました。";
                $found=true;
                break;
            }
        }
        if(!$found)$error="該当管理者が見つかりません。";
    }
}

// 管理者削除
if($action==='delete_admin'){
    $adminId=isset($_POST['admin_id'])?trim($_POST['admin_id']):'';
    if($adminId===''){
        $error="管理者IDが無効です。";
    } else {
        $found=false;
        foreach($usersData['accounts'] as $i=>$adm){
            if($adm['id']===$adminId){
                // 関連削除
                if(isset($adm['accounts'])){
                    foreach($adm['accounts'] as $accLink=>$accData){
                        if(is_dir($accLink)){
                            $files=new RecursiveIteratorIterator(
                                new RecursiveDirectoryIterator($accLink,RecursiveDirectoryIterator::SKIP_DOTS),
                                RecursiveIteratorIterator::CHILD_FIRST
                            );
                            foreach($files as $fileinfo){
                                $todo=($fileinfo->isDir()?'rmdir':'unlink');
                                $todo($fileinfo->getRealPath());
                            }
                            rmdir($accLink);
                        }
                        $dataDir='data/'.$accLink;
                        if(is_dir($dataDir)){
                            $files=new RecursiveIteratorIterator(
                                new RecursiveDirectoryIterator($dataDir,RecursiveDirectoryIterator::SKIP_DOTS),
                                RecursiveIteratorIterator::CHILD_FIRST
                            );
                            foreach($files as $fileinfo){
                                $todo=($fileinfo->isDir()?'rmdir':'unlink');
                                $todo($fileinfo->getRealPath());
                            }
                            rmdir($dataDir);
                        }
                        if(!empty($accData['icon'])&&file_exists($accData['icon']))unlink($accData['icon']);
                        foreach($accData['stories'] as $st){
                            if(file_exists($st['image']))unlink($st['image']);
                        }
                    }
                }
                unset($usersData['accounts'][$i]);
                $usersData['accounts']=array_values($usersData['accounts']);
                file_put_contents($accountJsonPath,json_encode($usersData,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
                $message="管理者(ID:$adminId)が正常に削除されました。";
                $found=true;
                break;
            }
        }
        if(!$found)$error="指定の管理者が見つかりません。";
    }
}

// 管理者レベル更新
if($action==='update_admin_level'){
    $adminId=isset($_POST['admin_id'])?trim($_POST['admin_id']):'';
    $newLevel=isset($_POST['new_level'])?intval($_POST['new_level']):1;
    if($adminId===''){
        $error="管理者IDが無効です。";
    } else {
        $found=false;
        foreach($usersData['accounts'] as &$adm){
            if($adm['id']===$adminId){
                $adm['level']=$newLevel;
                file_put_contents($accountJsonPath,json_encode($usersData,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
                $message="管理者(ID:$adminId)のレベルを$newLevelに変更しました。";
                $found=true;
                break;
            }
        }
        if(!$found)$error="指定の管理者が見つかりません。";
    }
}

// アカウント削除(管理者詳細から)
if($action==='delete_account_from_admin_detail'){
    $adminId=isset($_POST['admin_id'])?trim($_POST['admin_id']):'';
    $accLink=isset($_POST['account_link'])?trim($_POST['account_link']):'';
    if($adminId===''||$accLink===''){
        $error="無効なパラメータです。";
    } else {
        $found=false;
        foreach($usersData['accounts'] as &$adm){
            if($adm['id']===$adminId){
                if(isset($adm['accounts'][$accLink])){
                    $acc=$adm['accounts'][$accLink];
                    if(!empty($acc['icon']) && file_exists($acc['icon']))unlink($acc['icon']);
                    foreach($acc['stories'] as $st){
                        if(file_exists($st['image']))unlink($st['image']);
                    }

                    if(is_dir($accLink)){
                        $files=new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($accLink,RecursiveDirectoryIterator::SKIP_DOTS),
                            RecursiveIteratorIterator::CHILD_FIRST
                        );
                        foreach($files as $fileinfo){
                            $todo=($fileinfo->isDir()?'rmdir':'unlink');
                            $todo($fileinfo->getRealPath());
                        }
                        rmdir($accLink);
                    }

                    $dataDir='data/'.$accLink;
                    if(is_dir($dataDir)){
                        $files=new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($dataDir,RecursiveDirectoryIterator::SKIP_DOTS),
                            RecursiveIteratorIterator::CHILD_FIRST
                        );
                        foreach($files as $fileinfo){
                            $todo=($fileinfo->isDir()?'rmdir':'unlink');
                            $todo($fileinfo->getRealPath());
                        }
                        rmdir($dataDir);
                    }

                    unset($adm['accounts'][$accLink]);
                    file_put_contents($accountJsonPath,json_encode($usersData,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
                    $message="アカウント($accLink)を削除しました。";
                    $found=true;
                    break;
                }
            }
        }
        if(!$found)$error="指定の管理者またはアカウントが見つかりません。";
    }
}

// ストーリー削除(管理者詳細から)
if($action==='delete_story_from_admin_detail'){
    $adminId=isset($_POST['admin_id'])?trim($_POST['admin_id']):'';
    $accLink=isset($_POST['account_link'])?trim($_POST['account_link']):'';
    $stIndex=isset($_POST['story_index'])?intval($_POST['story_index']):-1;
    if($adminId===''||$accLink===''||$stIndex<0){
        $error="無効なパラメータです。";
    } else {
        $found=false;
        foreach($usersData['accounts'] as &$adm){
            if($adm['id']===$adminId){
                if(isset($adm['accounts'][$accLink]) && isset($adm['accounts'][$accLink]['stories'][$stIndex])){
                    $story=$adm['accounts'][$accLink]['stories'][$stIndex];
                    if(file_exists($story['image']))unlink($story['image']);
                    array_splice($adm['accounts'][$accLink]['stories'],$stIndex,1);
                    file_put_contents($accountJsonPath,json_encode($usersData,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
                    $message="ストーリーを削除しました。";
                    $found=true;
                    break;
                }
            }
        }
        if(!$found)$error="指定の管理者,アカウント,またはストーリーが見つかりません。";
    }
}

// ストーリー数計算
function countAdminStories($adm){
    $count=0;
    if(isset($adm['accounts'])){
        foreach($adm['accounts'] as $acc){
            $count+=count($acc['stories']);
        }
    }
    return $count;
}

// 全ストーリー一覧
$allStories=[];
foreach($usersData['accounts'] as $adm){
    $adminId=$adm['id'];
    if(isset($adm['accounts'])){
        foreach($adm['accounts'] as $accLink=>$acc){
            foreach($acc['stories'] as $idx=>$st){
                $allStories[]=[
                    "admin_id"=>$adminId,
                    "account_link"=>$accLink,
                    "account_name"=>$acc['name'],
                    "story_text"=>$st['text'],
                    "story_url"=>$st['url'],
                    "image"=>$st['image'],
                    "story_index"=>$idx
                ];
            }
        }
    }
}

// 管理者詳細画面
$viewAdminDetail=null;
if($viewAdmin!==''){
    foreach($usersData['accounts'] as $adm){
        if($adm['id']===$viewAdmin){
            $viewAdminDetail=$adm;
            break;
        }
    }
}

// ストーリー詳細
$viewStoryDetail=null;
if($viewStory!=='' && $viewStoryAcc!=='' && $viewStoryIndex>=0){
    foreach($usersData['accounts'] as $adm){
        if($adm['id']===$viewStory){
            if(isset($adm['accounts'][$viewStoryAcc]) && isset($adm['accounts'][$viewStoryAcc]['stories'][$viewStoryIndex])){
                $viewStoryDetail=[
                    "admin_id"=>$adm['id'],
                    "account_link"=>$viewStoryAcc,
                    "account_name"=>$adm['accounts'][$viewStoryAcc]['name'],
                    "story"=>$adm['accounts'][$viewStoryAcc]['stories'][$viewStoryIndex],
                    "story_index"=>$viewStoryIndex
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>System Overseer Portal</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body{
    font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
    background:#f9f9f9;
    margin:0;padding:20px;
}
h1,h2,h3{text-align:center;color:#333;}
.message{color:green;text-align:center;margin-bottom:20px;}
.error{color:red;text-align:center;margin-bottom:20px;}
.section{
    background:#fff;
    padding:20px;
    margin-bottom:20px;
    border-radius:8px;
    box-shadow:0 2px 4px rgba(0,0,0,0.1);
}
form{display:flex;flex-direction:column;max-width:400px;margin:0 auto;}
label{margin-bottom:10px;color:#555;}
input[type="text"],input[type="password"],input[type="number"]{
    padding:10px;border:1px solid #ccc;border-radius:4px;width:100%;box-sizing:border-box;
}
button{
    padding:10px;background:#0078D7;color:#fff;border:none;border-radius:4px;cursor:pointer;margin-top:10px;font-size:16px;
}
button:hover{background:#005a9e;}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}
th,td{
    border:1px solid #ddd;
    padding:8px;
    text-align:center;
}
th{
    background:#0078D7;color:#fff;
}
tr:nth-child(even){
    background:#f9f9f9;
}
.story-list{
    display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:20px;margin-top:20px;
}
.story-card{
    background:#f9f9f9;padding:10px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.1);
    text-align:center;cursor:pointer;
}
.story-card img{
    width:100%;height:120px;object-fit:cover;border-radius:4px;margin-bottom:10px;
}
.story-admin,.story-account,.story-text,.story-url{
    margin-bottom:5px;font-size:14px;color:#333;word-break:break-all;
}
.story-url a{color:#0078D7;}

.admin-detail-section {
    margin-top:20px;
}
.account-list-admin-detail,.story-list-admin-detail{
    margin-top:20px;
    background:#fff;padding:20px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.1);
}
.account-list-admin-detail h3, .story-list-admin-detail h3{
    margin-top:0;color:#333;
}
.account-item{
    background:#f9f9f9;padding:10px;margin-bottom:10px;border-radius:8px;
}
.account-item h4{margin:0 0 10px 0;color:#333;}
.account-item button{margin-right:10px;}

.story-detail-section {
    margin-top:20px;background:#fff;padding:20px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.1);
}
.story-detail-section img{
    max-width:100%;height:auto;display:block;margin-bottom:10px;
}
</style>
</head>
<body>
<h1>System Overseer Portal</h1>
<?php if(!empty($message)):?><p class="message"><?php echo esc($message);?></p><?php endif;?>
<?php if(!empty($error)):?><p class="error"><?php echo esc($error);?></p><?php endif;?>

<?php if($viewStoryDetail!==null):
    $st=$viewStoryDetail['story'];
?>
<h2>ストーリー詳細</h2>
<div class="story-detail-section">
    <img src="<?php echo esc($st['image']);?>" alt="ストーリー画像">
    <p>アカウント名: <?php echo esc($viewStoryDetail['account_name']);?></p>
    <p>テキスト: <?php echo esc($st['text']);?></p>
    <p>URL: <a href="<?php echo esc($st['url']);?>" target="_blank"><?php echo esc($st['url']);?></a></p>
    <p>閲覧数: <?php echo esc($st['access_count']);?></p>
    <p>遷移数: <?php echo esc($st['redirect_count']);?></p>
    <form method="post">
        <input type="hidden" name="action" value="delete_story_from_admin_detail">
        <input type="hidden" name="admin_id" value="<?php echo esc($viewStoryDetail['admin_id']);?>">
        <input type="hidden" name="account_link" value="<?php echo esc($viewStoryDetail['account_link']);?>">
        <input type="hidden" name="story_index" value="<?php echo esc($viewStoryDetail['story_index']);?>">
        <button type="submit">ストーリー削除</button>
    </form>
</div>
<p style="text-align:center;margin-top:20px;"><a href="sys_overseer_portal.php">戻る</a></p>
<?php exit;endif;?>

<?php if($viewAdminDetail!==null):
    $adm=$viewAdminDetail;
?>
<h2>管理者詳細(ID: <?php echo esc($adm['id']);?>)</h2>
<div class="section admin-detail-section">
    <p>UUID: <?php echo esc($adm['uuid']);?></p>
    <p>PW変更済: <?php echo $adm['pw_changed']?'はい':'いいえ';?></p>
    <p>レベル: <?php echo esc($adm['level']);?></p>
    <p>無効化: <?php echo isset($adm['disabled'])&&$adm['disabled']===true?'はい':'いいえ';?></p>

    <h3>管理者操作</h3>
    <form method="post">
        <input type="hidden" name="action" value="disable_admin">
        <input type="hidden" name="admin_id" value="<?php echo esc($adm['id']);?>">
        <button type="submit">この管理者を停止</button>
    </form>
    <form method="post">
        <input type="hidden" name="action" value="delete_admin">
        <input type="hidden" name="admin_id" value="<?php echo esc($adm['id']);?>">
        <button type="submit">この管理者を削除</button>
    </form>
    <form method="post">
        <input type="hidden" name="action" value="update_admin_level">
        <input type="hidden" name="admin_id" value="<?php echo esc($adm['id']);?>">
        <label>レベル変更:
            <input type="number" name="new_level" min="1" value="<?php echo esc($adm['level']);?>" required>
        </label>
        <button type="submit">レベル更新</button>
    </form>

    <?php if(isset($adm['accounts'])&&!empty($adm['accounts'])):?>
    <div class="account-list-admin-detail">
        <h3>アカウント一覧</h3>
        <?php foreach($adm['accounts'] as $accLink=>$accData):?>
        <div class="account-item">
            <h4>アカウント: <?php echo esc($accData['name']);?> (<?php echo esc($accLink);?>)</h4>
            <p>ストーリー数: <?php echo count($accData['stories']);?></p>
            <form method="post" style="display:inline-block;">
                <input type="hidden" name="action" value="delete_account_from_admin_detail">
                <input type="hidden" name="admin_id" value="<?php echo esc($adm['id']);?>">
                <input type="hidden" name="account_link" value="<?php echo esc($accLink);?>">
                <button type="submit">アカウント削除</button>
            </form>
            <h4>ストーリー一覧</h4>
            <?php if(!empty($accData['stories'])):?>
            <?php foreach($accData['stories'] as $idx=>$st):?>
            <div style="background:#eee;padding:5px;margin-bottom:5px;border-radius:4px;">
                <p>テキスト: <?php echo esc($st['text']);?></p>
                <p>URL: <a href="<?php echo esc($st['url']);?>" target="_blank"><?php echo esc($st['url']);?></a></p>
                <p>閲覧数: <?php echo esc($st['access_count']);?> / 遷移数: <?php echo esc($st['redirect_count']);?></p>
                <form method="post" style="display:inline-block;">
                    <input type="hidden" name="action" value="delete_story_from_admin_detail">
                    <input type="hidden" name="admin_id" value="<?php echo esc($adm['id']);?>">
                    <input type="hidden" name="account_link" value="<?php echo esc($accLink);?>">
                    <input type="hidden" name="story_index" value="<?php echo $idx;?>">
                    <button type="submit">ストーリー削除</button>
                </form>
            </div>
            <?php endforeach; else:?>
            <p>ストーリーがありません。</p>
            <?php endif;?>
        </div>
        <?php endforeach;?>
    </div>
    <?php else:?>
    <p>この管理者にはアカウントがありません。</p>
    <?php endif;?>

</div>
<p style="text-align:center;margin-top:20px;"><a href="sys_overseer_portal.php">戻る</a></p>
<?php exit;endif;?>

<div class="section">
    <h2>管理者の作成</h2>
    <form method="post">
        <label>管理者ID：
            <input type="text" name="admin_id" placeholder="例: newadmin" required>
        </label>
        <label>管理者パスワード：
            <input type="password" name="admin_pw" placeholder="パスワード" required>
        </label>
        <label>管理者レベル：
            <input type="number" name="admin_level" min="1" value="1" required>
        </label>
        <input type="hidden" name="action" value="create_admin">
        <button type="submit">管理者作成</button>
    </form>
</div>

<div class="section">
    <h2>管理者の削除</h2>
    <form method="post">
        <label>管理者ID：
            <input type="text" name="admin_id" required>
        </label>
        <input type="hidden" name="action" value="delete_admin">
        <button type="submit">管理者削除</button>
    </form>
</div>

<div class="section">
    <h2>管理者レベルの新規作成</h2>
    <form method="post">
        <label>レベル：
            <input type="number" name="new_level" min="1" required>
        </label>
        <label>最大アカウント数：
            <input type="number" name="new_max_accounts" min="1" value="10" required>
        </label>
        <label>最大ストーリー数：
            <input type="number" name="new_max_stories" min="1" value="50" required>
        </label>
        <input type="hidden" name="action" value="create_level">
        <button type="submit">レベル作成</button>
    </form>
</div>

<div class="section">
    <h2>管理者レベルの編集</h2>
    <form method="post">
        <label>レベル：
            <input type="number" name="level" min="1" required>
        </label>
        <label>最大アカウント数：
            <input type="number" name="max_accounts" min="1" value="10" required>
        </label>
        <label>最大ストーリー数：
            <input type="number" name="max_stories" min="1" value="50" required>
        </label>
        <input type="hidden" name="action" value="update_level">
        <button type="submit">設定更新</button>
    </form>
</div>

<div class="section">
    <h2>全管理者一覧</h2>
    <?php if(!empty($usersData['accounts'])):?>
    <table>
        <tr><th>UUID</th><th>ID</th><th>PW</th><th>PW変更済?</th><th>レベル</th><th>無効?</th><th>アカウント数</th><th>ストーリー数</th><th>詳細</th></tr>
        <?php foreach($usersData['accounts'] as $adm):
            $accCount=isset($adm['accounts'])?count($adm['accounts']):0;
            $stCount=0;
            if(isset($adm['accounts'])){
                foreach($adm['accounts'] as $acc){
                    $stCount+=count($acc['stories']);
                }
            }
            ?>
        <tr>
            <td><?php echo esc($adm['uuid']);?></td>
            <td><?php echo esc($adm['id']);?></td>
            <td><?php echo esc($adm['pw']);?></td>
            <td><?php echo $adm['pw_changed']?'はい':'いいえ';?></td>
            <td><?php echo esc($adm['level']);?></td>
            <td><?php echo isset($adm['disabled'])&&$adm['disabled']===true?'はい':'いいえ';?></td>
            <td><?php echo esc($accCount);?></td>
            <td><?php echo esc($stCount);?></td>
            <td><a href="sys_overseer_portal.php?admin=<?php echo urlencode($adm['id']);?>">詳細</a></td>
        </tr>
        <?php endforeach;?>
    </table>
    <?php else:?>
    <p style="text-align:center;color:#555;">管理者がいません。</p>
    <?php endif;?>
</div>

<div class="section">
    <h2>管理者レベル一覧</h2>
    <?php if(!empty($systemData['admin_levels'])):?>
    <table>
        <tr><th>レベル</th><th>最大アカウント数</th><th>最大ストーリー数</th></tr>
        <?php foreach($systemData['admin_levels'] as $lvl=>$limits):?>
        <tr>
            <td><?php echo esc($lvl);?></td>
            <td><?php echo esc($limits['max_accounts']);?></td>
            <td><?php echo esc($limits['max_stories']);?></td>
        </tr>
        <?php endforeach;?>
    </table>
    <?php else:?>
    <p style="text-align:center;color:#555;">レベル設定がありません。</p>
    <?php endif;?>
</div>

<div class="section">
    <h2>全管理者の全ストーリー一覧</h2>
    <?php if(!empty($allStories)):?>
    <div class="story-list">
        <?php foreach($allStories as $st):?>
        <a href="sys_overseer_portal.php?story_admin=<?php echo urlencode($st['admin_id']);?>&story_acc=<?php echo urlencode($st['account_link']);?>&story_index=<?php echo $st['story_index'];?>" class="story-card">
            <img src="<?php echo esc($st['image']);?>" alt="ストーリー画像">
            <div class="story-admin">管理者ID: <?php echo esc($st['admin_id']);?></div>
            <div class="story-account">アカウント名: <?php echo esc($st['account_name']);?></div>
            <div class="story-text">テキスト: <?php echo esc($st['story_text']);?></div>
            <div class="story-url">URL: <?php echo esc($st['story_url']);?></div>
        </a>
        <?php endforeach;?>
    </div>
    <?php else:?>
    <p style="text-align:center;color:#555;">ストーリーがありません。</p>
    <?php endif;?>
</div>

</body>
</html>
