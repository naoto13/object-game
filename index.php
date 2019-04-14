<!-- full -->

<?php
ini_set('log_errors','on');
ini_set('error_log','php.log');
session_start();

// 自分のHP
define("MY_HP", 500);
// モンスター達格納用
$monsters = array();

$monsters[] = array(
    'name' => 'フランケン',
    'hp' => 100,
    'img' => 'img/monster01.png',
    //最小と最大で攻撃力を決定。整数のみ
    'attack' => mt_rand(20, 40)
);
$monsters[] = array(
    'name' => 'フランケンNEO',
    'hp' => 300,
    'img' => 'img/monster02.png',
    'attack' => mt_rand(20, 60)
);
$monsters[] = array(
    'name' => 'ドラキュリー',
    'hp' => 200,
    'img' => 'img/monster03.png',
    'attack' => mt_rand(30, 50)
);
$monsters[] = array(
  'name' => 'ドラキュリー男爵',
  'hp' => 400,
  'img' => 'img/monster04.png',
  'attack' => mt_rand(50, 100)
);
$monsters[] = array(
  'name' => 'スカルフェイス',
  'hp' => 150,
  'img' => 'img/monster05.png',
  'attack' => mt_rand(30, 60)
);
$monsters[] = array(
  'name' => '毒ハンド',
  'hp' => 100,
  'img' => 'img/monster06.png',
  'attack' => mt_rand(10, 30)
);
$monsters[] = array(
  'name' => '泥ハンド',
  'hp' => 120,
  'img' => 'img/monster07.png',
  'attack' => mt_rand(20, 30)
);
$monsters[] = array(
  'name' => '血のハンド',
  'hp' => 180,
  'img' => 'img/monster08.png',
  'attack' => mt_rand(30, 50)
);

function createMonster(){
    global $monsters;
    //８体のモンスターのどれかのkeyを取得する。
    $viewMonster = $monsters[mt_rand(0, 7)];
    //初期化
    unset($_SESSION['name']);
    unset($_SESSION['hp']);
    unset($_SESSION['img']);
    $_SESSION['name'] = $viewMonster['name'];
    $_SESSION['hp'] = $viewMonster['hp'];
    $_SESSION['img'] = $viewMonster['img'];
    $_SESSION['attack'] = $viewMonster['attack'];
    $_SESSION['history'] .= $_SESSION['name'].'が現れた！<br>';
}
function init(){
    $_SESSION['history'] .= '初期化します！<br>';
    // knockDownCountは倒したモンスターの数のカウント
    $_SESSION['knockDownCount'] = 0;
    $_SESSION['myhp'] = MY_HP;
    createMonster();
}
// ゲームオーバーの場合はセッションクリア→スタート画面へ
function gameOver(){
    $_SESSION = array();
}

//1.POST送信されていた場合
if(!empty($_POST)){
    $attackFlg = (!empty($_POST['attack'])) ? true : false;
    $startFlg = (!empty($_POST['start'])) ? true : false;
    error_log('POSTされた！');

    if($startFlg){
        $_SESSION['history'] = 'ゲームスタート！<br>';
        //initは初期化関数
        init();
    }else{
        // 攻撃するを押した場合
        if($attackFlg){
            $_SESSION['history'] .= '攻撃した！<br>';
    // ランダムでモンスターに攻撃を与える
        $attackPoint = mt_rand(50,100);
        //hp = hp - attackpoint を-=として省略
        $_SESSION['hp'] -= $attackPoint;
        $_SESSION['history'] .= $attackPoint.'ポイントのダメージを与えた！<br>';
    // モンスターから攻撃を受ける
        $_SESSION['myhp'] -= $_SESSION['attack'];
        $_SESSION['history'] .= $_SESSION['attack'].'ポイントのダメージを受けた！<br>';

        // 自分のhpが0以下になったらゲームオーバー
            if($_SESSION['myhp'] <= 0){
                gameOver();
            }else{
                // 敵のhp（hp）が残っていたら、別のモンスターを出現させる
                if($_SESSION['hp'] <= 0){
                    $_SESSION['history'] .= $_SESSION['name'].'を倒した！<br>';
                    createMonster();
                    $_SESSION['knockDownCount'] = $_SESSION['knockDownCount']+1;
                }
            }
        }else{ //逃げるを押した場合
        $_SESSION['history'] .= '逃げた！<br>';
        createMonster();
        }
    }
    $_POST = array();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>タイトル</title>

    <style>
        body{
            margin: 0 auto;
            padding: 150px;
            width: 25%;
            background: #fbfbfa;
            color: white;
        }
        h1{ color: white; font-size: 20px; text-align: center;}
        h2{ color: white; font-size: 16px; text-align: center;}
        form{
            overflow: hidden;
        }
        input[type="text"]{
            color: #545454;
            height: 60px;
            width: 100%;
            padding: 5px 10px;
            font-size: 16px;
            display: block;
            margin-bottom: 10px;
            box-sizing: border-box;
        }
        input[type="password"]{
            color: #545454;
            height: 60px;
            width: 100%;
            padding: 5px 10px;
            font-size: 16px;
            display: block;
            margin-bottom: 10px;
            box-sizing: border-box;
        }
        input[type="submit"]{
            border: none;
            padding: 15px 30px;
            margin-bottom: 15px;
            background: black;
            color: white;
            float: right;
        }
        input[type="submit"]:hover{
            background: #3d3938;
            cursor: pointer;
        }
        a{
            color: #545454;
            display: block;
        }
        a:hover{
            text-decoration: none;
        }
    </style>
</head>
<body>
    <h1 style="text-align:center; color:#333;">ゲーム「ドラ◯エ！」</h1>
    <div style="background:black; padding:15px; position:relative;">
        <!-- SESSIONがからの場合はスタート画面へ -->
        <?php if(empty($_SESSION)){ ?>
            <h2 style="margin-top:60px;">GAME START ?</h2>
            <form method="post">
                <input type="submit" name="start" value="▶ゲームスタート">
            </form>
        <?php }else{ ?>
            <h2><?php echo $_SESSION['name'].'が現れた!!'; ?></h2>
            <div style="height: 150px;">
                <img src="<?php echo $_SESSION['img']; ?>" style="width:120px; height:auto; margin:40px auto 0 auto; display:block;">
            </div>
            <p style="font-size:14px; text-align:center;">モンスターのHP：<?php echo $_SESSION['hp']; ?></p>
            <p>倒したモンスター数：<?php echo $_SESSION['knockDownCount']; ?></p>
            <p>勇者の残りHP：<?php echo $_SESSION['myhp']; ?></p>
            <!-- formタグでボタンを生成 -->
            <form method="post">
                <input type="submit" name="attack" value="▶攻撃する">
                <input type="submit" name="escape" value="▶逃げる">
                <input type="submit" name="start" value="▶ゲームリスタート">
            </form>
        <?php } ?>
        <!-- サイドバー -->
        <div style="position:absolute; right:-300px; top:0; color:black; width: 250px;">
            <p><?php echo (!empty($_SESSION['history'])) ? $_SESSION['history'] : ''; ?></p>
        </div>
    </div>
</body>
</html>