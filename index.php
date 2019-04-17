<!-- full -->

<?php
ini_set('log_errors','on');
ini_set('error_log','php.log');
session_start();

// 自分のHP
define("MY_HP", 500);
// モンスター達格納用
$monsters = array();
//クラス（設計図）の作成。クラス名の戦闘は大文字で
class Monster{
//プロパティ　変数はpublicをつける
    private $name; // 定義しただけだとnullが入る
    private $hp;
    private $img;
    private $attack = ''; // nullを入れたくない場合、空文字などで初期化する
    //コンストラクタも関数（__が目印）
    public function __construct($name, $hp, $img, $attack) {
        //this（自分自身のプロパティ）にアクセスしたい
        $this->name = $name;
        $this->hp = $hp;
        $this->img = $img;
        $this->attack = $attack;
    }
    //attackメソッド(これで使いまわせる)
    public function attack(){
        $_SESSION['myhp'] -= $this->attack;
        $_SESSION['history'] .= $this->attack.'ポイントのダメージを受けた！<br>';
    }
//セッター2つ（setHP, setAttack）
    public function setHp($num){
        //セッターを使うことで、直接代入させずにバリデーションチェックを行ってから代入させることができる
        // FILTER_VALIDATE_INTでint型かどうかを確認（小数点の排除）
        $this->hp = filter_var($num, FILTER_VALIDATE_INT);
        //filter_varは値に対して色々なパターンのバリデーションを行える便利関数
    }
    public function setAttack($num){
        //$numには小数点が入る可能性がある。filter_var関数はバリデーションに引っかかると
        // falseが帰ってきて代入されてしまうので、float型かどうかのバリデーションにしてint型へキャスト.
        // （falseのままだとattackに、０となり代入されてしまう）
        // もしくは、FILTER_VALIDATE_FLOATを使う。
        $this->attack = (int)filter_var($num, FILTER_VALIDATE_FLOAT);
    }
//ゲッター4つ（getName, getHp, getImg, getAttack）
    public function getName(){
        return $this->name;
    }
    public function getHp(){
        return $this->hp;
    }
    public function getImg(){
        // あとあとでimgが入っていなかったら、no-imgをだそう！となった時でも、クラスを書き換えるだけ！
        // もしゲッターメソッドを使っていなければ、取得するコードのかしょ全部を修正しなければいけない！
        // カプセル化をすることで、呼び出す側は「中で何をしているのか」を気にせず、ただ呼び出せばいいだけになる（疎結合）
        if(empty($this->img)){
            return 'img/no-img.png';
        }
        return $this->img;
    }
    public function getAttack(){
        return $this->attack;
    }
}

//インスタンス生成 それぞれ初期値を入れている。（必ず必要というわけではない）
$monsters[] = new Monster( 'フランケン', 100, 'img/monster01.png', mt_rand(20, 40) );
$monsters[] = new Monster( 'フランケンNEO', 300, 'img/monster02.png', mt_rand(20, 60) );
$monsters[] = new Monster( 'ドラキュリー', 200, 'img/monster03.png', mt_rand(30, 50) );
$monsters[] = new Monster( 'ドラキュラ男爵', 400, 'img/monster04.png', mt_rand(50, 80) );
$monsters[] = new Monster( 'スカルフェイス', 150, 'img/monster05.png', mt_rand(30, 60) );
$monsters[] = new Monster( '毒ハンド', 100, 'img/monster06.png', mt_rand(10, 30) );
$monsters[] = new Monster( '泥ハンド', 120, 'img/monster07.png', mt_rand(20, 30) );
$monsters[] = new Monster( '血のハンド', 180, 'img/monster08.png', mt_rand(30, 50) );

//インスタンスのなかのプロパティ（属性）にアクセスするときはアロー関数が必要
// (今までのような連想配列だったら$monster['name']だったが、インスタンスは->だけで良い)
// monstarは呼び出された１体。monstersはインスタンス
function createMonster(){
    global $monsters;
    $monster = $monsters[mt_rand(0, 7)];
    // privateなのでnameに直接アクセスできない。そのためゲッターメソッドを使用
    $_SESSION['history'] .= $monster->getName().'が現れた！<br>';
    // セッションの中にはインスタンスがそのまま入っている
    $_SESSION['monster'] =  $monster;
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
            // セッションの中のmonsterのhpを呼び出す
            $_SESSION['monster']->setHp( $_SESSION['monster']->getHp() - $attackPoint );
            $_SESSION['history'] .= $attackPoint.'ポイントのダメージを与えた！<br>';
        // モンスターから攻撃を受ける(attackメソッドを使用する)
            //10分の１の確率でモンスターのクリティカル
            // mt_randを否定（！をつける）することでint型がboolean型に変わるので、if(mt_rand(0,9) == $int)としなくても確率の計算ができる。
            if(!mt_rand(0,3)){ //0の場合だけfalseとなるのでif文の中に入る１〜９まではtrueなので、if以下には入らない
                $_SESSION['monster']->setAttack($_SESSION['monster']->getAttack()*1.5);
                $_SESSION['history'] .= $_SESSION['monster']->getName().'のクリティカルヒット!!<br>';
            }
            //$_SESSION['monster']->setAttack();
             $_SESSION['monster']->attack();

        // 自分のhpが0以下になったらゲームオーバー
            if($_SESSION['myhp'] <= 0){
                gameOver();
            }else{
                // 敵のhp（hp）が残っていたら、別のモンスターを出現させる
                if($_SESSION['monster']->getHp() <= 0){
                    $_SESSION['history'] .= $_SESSION['monster']->getName().'を倒した！<br>';
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
    <title>ドラクエ</title>

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
    <h1 style="text-align:center; color:#333;">ゲーム「ドラ◯エ!!」</h1>
    <div style="background:black; padding:15px; position:relative;">
        <!-- SESSIONがからの場合はスタート画面へ -->
        <?php if(empty($_SESSION)){ ?>
            
            <h2 style="margin-top:60px;">GAME START ?</h2>
            <form method="post">
                <input type="submit" name="start" value="▶ゲームスタート">
            </form>
        <?php }else{ ?>
            <h2><?php echo $_SESSION['monster']->getName().'が現れた!!'; ?></h2>
            <div style="height: 150px;">
                <img src="<?php echo $_SESSION['monster']->getImg(); ?>" style="width:120px; height:auto; margin:40px auto 0 auto; display:block;">
            </div>
            <p style="font-size:14px; text-align:center;">モンスターのHP：<?php echo $_SESSION['monster']->getHp(); ?></p>
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