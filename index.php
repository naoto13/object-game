<!-- full -->

<?php

ini_set('log_errors','on');
ini_set('error_log','php.log');
session_start();

// モンスター達格納用
$monsters = array();
//クラス（設計図）の作成。クラス名の先頭は大文字で

//性別クラス（クラス定数）
class Sex{
    const MAN = 1;
    const WOMAN = 2;
    const OKAMA = 3;
}

//抽象クラス（生き物クラス）
// 各クラスで共通するものを抜き出している。変わらないプロパティは、そのままprotectedで継承先でも使えるように
//クラスごとに変わるものは抽象メソッドとしてabstract public functionとして定義する
abstract class Creature{
    protected $name;
    protected $hp;
    protected $attackMin;
    protected $attackMax;
    abstract public function sayCry();

    // 共通して使いそうなセッターゲッターを定義
    public function setName($str){
        $this->name = $str;
    }
    public function getName(){
        return $this->name;
    }
    public function setHp($num){
        $this->hp = $num;
    }
    public function getHp(){
        return $this->hp;
    }
    // attackは以前までは引数なしattack()で人クラスやモンスタークラスの中に書いていたが
    //   これだと、攻撃する相手がmonster確定なので、混乱して自分を攻撃などができなくなる。
    //   そのためattackには攻撃する相手を引数としてratgetObjとする（疎結合）
    //モンスターも人もクリティカルは1/10で発生し、1.5倍の攻撃力と決まっていれば定義しちゃって良い。
    public function attack($targetObj){
        $attackPoint = mt_rand($this->attackMin, $this->attackMax);
        // mt_randを否定（！をつける）することでint型がboolean型に変わるので、if(mt_rand(0,9) == $int)としなくても確率の計算ができる。
        //0の場合だけfalseとなるのでif文の中に入る１〜９まではtrueなので、if以下には入らない
        if(!mt_rand(0,9)){//10分の１の確率でモンスターのクリティカル
            $attackPoint *= 1.5;
            $attackPoint = (int)$attackPoint;
            History::set($this->getName().'のクリティカルヒット!!');
        }
        $targetObj->setHp($targetObj->getHp()-$attackPoint);
        History::set($attackPoint.'ポイントのダメージ！');
    }
}

//人クラス（こうすることで後々勇者クラスなどに拡張（継承）できる）
class Human extends Creature{
    protected $sex;
    public function __construct($name, $sex, $hp, $attackMin, $attackMax) {
        $this->name = $name;
        $this->sex = $sex;
        $this->hp = $hp;
        $this->attackMin = $attackMin;
        $this->attackMax = $attackMax;
    }
    
    public function setSex($num){
        $this->sex = $num;
    }
    public function getSex(){
        return $this->sex;
    }
    
    // クラス定数を用いて、わかりやすくswitch文で分岐させる。
    public function sayCry(){
        History::set($this->name.'が叫ぶ！');
        switch($this->sex){
            case Sex::MAN :
                History::set('ぐはぁっ！');
                break;
            case Sex::WOMAN :
                History::set('きゃっ！');
                break;
            case Sex::OKAMA :
                History::set('もっと！♡');
                break;
        }
    }
}

//モンスタークラス
class Monster extends Creature{
//プロパティ　継承先でも使用したいのでセレクタはprotectedにする
    protected $img;
    //コンストラクタも関数（__が目印）
    public function __construct($name, $hp, $img, $attackMin, $attackMax) {
        //this（自分自身のプロパティ）にアクセスしたい
        $this->name = $name;
        $this->hp = $hp;
        $this->img = $img;
        $this->attackMin = $attackMin;
        $this->attackMax = $attackMax;
    }
    //ゲッター
    public function getImg(){
        // あとあとでimgが入っていなかったら、no-imgをだそう！となった時でも、クラスを書き換えるだけ！
        // もしゲッターメソッドを使っていなければ、取得するコードのかしょ全部を修正しなければいけない！
        // カプセル化をすることで、呼び出す側は「中で何をしているのか」を気にせず、ただ呼び出せばいいだけになる（疎結合）
        // if(empty($this->img)){
        //     return 'img/no-img.png';
        // }
        return $this->img;
    }
    public function sayCry(){
        History::set($this->name.'が叫ぶ！');
        History::set('はうっ！');
    }
}

//魔法を使えるモンスタークラス（継承）
class MagicMonster extends Monster{
    private $magicAttack;
    //継承しているのでこのクラスで宣言していないもの（magicAttack）以外を呼び出せる
    function __construct($name, $hp, $img, $attackMin, $attackMax, $magicAttack) {
        //親クラスのコンストラクタで処理する内容を継承したい場合には親コンストラクタを呼び出す
        parent::__construct($name, $hp, $img, $attackMin, $attackMax);
        $this->magicAttack = $magicAttack;
    }
    public function getMagicAttack(){
        return $this->magicAttack;
    }
    //セッターゲッターなども引き継がれている
    //魔法攻撃力が増えることはない前提として、セッターは作らない
    // public function magicAttack(){
    //     $_SESSION['history'] .= $this->name.'の魔法攻撃!!<br>';
    //     $_SESSION['myhp'] -= $this->magicAttack;
    //     $_SESSION['history'] .= $this->magicAttack.'ポイントのダメージを受けた！<br>';
    // }
    //Attackメソッドをオーバーライドすることで、「ゲーム進行を管理する処理側」は単にattackメソッドを呼べばいいだけになる
    // 魔法を使えるモンスターは自分で魔法を出すか普通に攻撃するかを判断する
    public function attack($targetObj){
        if(!mt_rand(0,4)){ //5分の1の確率で魔法攻撃
            History::set($this->name.'の魔法攻撃!!');
            $targetObj->setHp( $targetObj->getHp() - $this->magicAttack );
            History::set($this->magicAttack.'ポイントのダメージを受けた！');
        }else{
            //通常の攻撃の場合は、親クラスの攻撃メソッドを使うことで
            //親クラスの攻撃メソッドが修正されてもMagicMonsterでも反映される（呼んであげるだけ）
            parent::attack($targetObj);
        }
    }
}

// //セッター2つ（setHP, setAttack）
//     public function setHp($num){
//         //セッターを使うことで、直接代入させずにバリデーションチェックを行ってから代入させることができる
//         // FILTER_VALIDATE_INTでint型かどうかを確認（小数点の排除）
//         $this->hp = filter_var($num, FILTER_VALIDATE_INT);
//         //filter_varは値に対して色々なパターンのバリデーションを行える便利関数
//     }
//     public function setAttack($num){
//         $this->attack = (int)filter_var($num, FILTER_VALIDATE_FLOAT);
//     }

// //ゲッター（getName, getHp, getImg, getAttack）
//     public function getName(){
//         return $this->name;
//     }
//     public function getHp(){
//         return $this->hp;
//     }
//     public function getImg(){
//         // あとあとでimgが入っていなかったら、no-imgをだそう！となった時でも、クラスを書き換えるだけ！
//         // もしゲッターメソッドを使っていなければ、取得するコードのかしょ全部を修正しなければいけない！
//         // カプセル化をすることで、呼び出す側は「中で何をしているのか」を気にせず、ただ呼び出せばいいだけになる（疎結合）
//         if(empty($this->img)){
//             return 'img/no-img.png';
//         }
//         return $this->img;
//     }
//     public function getAttack(){
//         return $this->attack;
//     }
// }

//履歴管理クラス（インスタンス化して複数に増殖させる必要のないクラスなので、静的メンバ（static）にする）
class History{
    // 引数strを履歴に残す
    public static function set($str){
        //セッションhistoryが作られてなければ作る
        if(empty($_SESSION['history'])) $_SESSION['history'] = '';
        //文字列をセッションhistoryへ移動
        $_SESSION['history'] .= $str.'<br>';
    }
    public static function clear(){
        unset($_SESSION['history']);
    }
}



//インスタンス生成 それぞれ初期値を入れている。（必ず必要というわけではない）
//性別は３などの筋で設定可能だが、可読性が悪くなるため、クラス定数を使用し、クラス名::中身　と書くことでわかりやすくなる
// define(WOMAN,2);を用いて下のように書くこともできるが、なんのWOMANなのかわかりづらいため▽
// $human = new Human('勇者見習い',WOMAN, 500, 40, 120);

$human = new Human('勇者見習い', Sex::MAN, 500, 40, 120);
$monsters[] = new Monster( 'フランケン', 100, 'img/monster01.png', 20, 40 );
$monsters[] = new MagicMonster( 'フランケンNEO', 300, 'img/monster02.png', 20, 60, mt_rand(50, 100) );
$monsters[] = new Monster( 'ドラキュリー', 200, 'img/monster03.png', 30, 50 );
$monsters[] = new MagicMonster( 'ドラキュラ男爵', 400, 'img/monster04.png', 50, 80, mt_rand(60, 120) );
$monsters[] = new Monster( 'スカルフェイス', 150, 'img/monster05.png', 30, 60 );
$monsters[] = new Monster( '毒ハンド', 100, 'img/monster06.png', 10, 30 );
$monsters[] = new Monster( '泥ハンド', 120, 'img/monster07.png', 20, 30 );
$monsters[] = new Monster( '血のハンド', 180, 'img/monster08.png', 30, 50 );

//インスタンスのなかのプロパティ（属性）にアクセスするときはアロー関数が必要
// (今までのような連想配列だったら$monster['name']だったが、インスタンスは->だけで良い)
// monstarは呼び出された１体。monstersはインスタンス
function createMonster(){
    global $monsters;
    $monster =  $monsters[mt_rand(0, 7)];
    // privateなのでnameに直接アクセスできない。そのためゲッターメソッドを使用
    // $_SESSION['history'] .= $monster->getName().'が現れた！<br>';
    History::set($monster->getName().'が現れた！');
    // セッションの中にはインスタンスがそのまま入っている
    $_SESSION['monster'] =  $monster;
}

function createHuman(){
    global $human;
    // セッションにhumanという変数を作り、humanのインスタンスを中に入れる
    $_SESSION['human'] =  $human;
}

function init(){
    History::clear();
    History::set('初期化します！');
    // knockDownCountは倒したモンスターの数のカウント
    $_SESSION['knockDownCount'] = 0;
    createHuman();
    createMonster();
}
// ゲームオーバーの場合はセッションクリア→スタート画面へ
function gameOver(){
    // debug('セッションクリアします！');
    $_SESSION = array();
    createHuman();
    createMonster();
}


//1.POST送信されていた場合
if(!empty($_POST)){
    $attackFlg = (!empty($_POST['attack'])) ? true : false;
    $startFlg = (!empty($_POST['start'])) ? true : false;
    error_log('POSTされた！');

    if($startFlg){
        History::set('ゲームスタート！');
        //initは初期化関数
        init();
    }else{
        // 攻撃するを押した場合
        if($attackFlg){

        // ランダムでモンスターに攻撃を与える
            History::set($_SESSION['human']->getName().'の攻撃！');
            // attackメソッドの引数に対象を入れれるようにすることで、自分自身も攻撃対象に設定可能に
            $_SESSION['human']->attack($_SESSION['monster']);
            $_SESSION['monster']->sayCry();
            // $attackPoint = mt_rand(50,100);
            //hp = hp - attackpoint を-=として省略
            // セッションの中のmonsterのhpを呼び出す
            //ランダムでモンスターに攻撃を与える
            // $_SESSION['monster']->setHp( $_SESSION['monster']->getHp() - $attackPoint );
            // History::set($attackPoint.'ポイントのダメージを与えた！');
            

        // モンスターから攻撃を受ける(attackメソッドを使用する)
            History::set($_SESSION['monster']->getName().'の攻撃！');
            $_SESSION['monster']->attack($_SESSION['human']);
            $_SESSION['human']->sayCry();
            // if($_SESSION['monster'] instanceof MagicMonster){ //魔法攻撃の行えるモンスターなら
            //     if(!mt_rand(0,4)){ //5分の1の確率で魔法攻撃
            //         $_SESSION['monster']->magicAttack();
            //     }else{
            //         $_SESSION['monster']->attack();
            //     }
            // }else{ //普通のモンスターなら普通の攻撃
            //  $_SESSION['monster']->attack();
            // }
        //人が叫ぶ
            // $_SESSION['human']->sayCry();

        // 自分のhpが0以下になったらゲームオーバー
            if($_SESSION['human']->getHp() <= 0){
                // debug('HPは　です。ゲームオーバーです');
                gameOver();
            }else{
                // 敵のhp（hp）が残っていたら、別のモンスターを出現させる
                if($_SESSION['monster']->getHp() <= 0){
                    History::set($_SESSION['monster']->getName().'を倒した！');
                    History::set('------------------------------------------');
                    createMonster();
                    $_SESSION['knockDownCount'] = $_SESSION['knockDownCount']+1;
                }
            }
        }else{ //逃げるを押した場合
        History::set('逃げた！');
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
            padding: 50px;
            padding-top: 10px;
            width: 45%;
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
            <p>勇者の残りHP：<?php echo $_SESSION['human']->getHp(); ?></p>
            <!-- formタグでボタンを生成 -->
            <form method="post">
                <input type="submit" name="attack" value="▶攻撃する">
                <input type="submit" name="escape" value="▶逃げる">
                <input type="submit" name="start" value="▶ゲームリスタート">
            </form>
        <?php } ?>
        <!-- サイドバー -->
        <div style="position:absolute; right:-350px; top:0; color:black; width: 300px;">
            <p><?php echo (!empty($_SESSION['history'])) ? $_SESSION['history'] : ''; ?></p>
        </div>
    </div>
</body>
</html>