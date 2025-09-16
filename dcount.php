<?php

// /************************************
//  *   昨日と今日のカウンタ by ToR
//  *		http://php.s3.to
//  *		2000/05/08
//  *              2000/05/29 バグ修正、F2s対策
//  ************************************
//  * 昨日、今日、合計のカウンタを、IMGタグで
//  * 画像もしくはテキストにて出力します。
//  * 
//  * 同一IPからの連続アクセスはカウントしない機能付
//  *
//  * ＋準備
//  *   合計用の空ファイル(all.dat)を作成し、
//  *   パーミッションを666に変更します。
//  *
//  * ＋使い方
//  *   .php等にした、PHPが動くファイル内で、
//  *   まず\<\? include ("dcount.php"); \?\>を挿入します。
//  *   そしてカウンタを置きたい場所に、次のように入れます
//  *
//  *   きょうは\<\?echo $today;\?\>人目 昨日は\<\?echo $yesterday;\?\>人
//  *     　　いままでに\<\?echo $total;\?\>人も来たよ。
//  *
//  *   注：カウンタを挿入する前に必ず
//  *   \<\? include("dcount.php"); \?\>
//  *   コレを入れておいてください。
//  * 
//  */
//------------設定----------
//テキストカウンタなら0 画像カウンタなら1 
$counter_mode     = 0;
// 昨日カウント用GIF画像のディレクトリ
$yes_path = './img/cntimg2/';
// 本日カウント用GIF画像のディレクトリ
$day_path = './img/cntimg2/';
// 総カウント用GIF画像のディレクトリ
$all_path = './img/cntimg2/';
// カウンタ記録ファイル
$log     = './all.dat';
// 昨日カウント数の桁数
$fig1    = 2;
// 本日カウント数の桁数
$fig2    = 2;
// 合計カウント数の桁数
$fig3    = 3;
// 連続IPはカウントしない（yes=1 no=0)
$ipcheck = 1;

//---------設定ここまで------
function outhtml($f_cnt, $c_path)
{ //カウント数とパスを与えて、IMGタグを返す
	$i_tag = "";
	$size = getimagesize($c_path . "0.gif");  //0.gifからwidthとheight取得
	for ($i = 0; $i < strlen($f_cnt); $i++):	//桁数分だけループ
		$n = substr($f_cnt, $i, 1);			//左から一桁ずつ取得
		$i_tag .= "<IMG SRC=\"$c_path$n.gif\" alt=$n $size[3]>";
	endfor;

	return $i_tag;
}

$now_date = gmdate("Ymd", time() + 9 * 3600);	// 今日の日付
$yes_date = gmdate("Ymd", time() - 15 * 3600);	// -24h
$dat = file($log);				// ファイルを配列に
if (!$dat) {
	return;
}
list($key, $yes, $tod, $all, $addr) = explode("|", $dat[0]); //データを分解
$ip = getenv('REMOTE_ADDR');

if (($ipcheck && $ip != "$addr") || $ipcheck == 0) { //直前IPが違うならｶｳﾝﾄｱｯﾌﾟ
	if ($key == $now_date) { //キーが今日なら今日をｱｯﾌﾟ
		$tod++;
	} else {
		$yes = ($key == $yes_date) ? $tod : 0; //キーが昨日なら昨日に今日ｶｳﾝﾄ格納それ以外は0
		$tod = 1;
	}
	$all++; //合計をｶｳﾝﾄｱｯﾌﾟ
	$new = implode("|", array($now_date, $yes, $tod, $all, $ip)); //データ連結
	$fp = fopen($log, "w"); //ファイルに保存
	flock($fp, 2);
	fputs($fp, $new);
	fclose($fp);
}
//カウント整形（空いた桁を0で埋める）
$yesterday = sprintf("%0" . $fig1 . "d", $yes);
$today = sprintf("%0" . $fig2 . "d", $tod);
$total = sprintf("%0" . $fig3 . "d", $all);

if ($counter_mode) {
	//タグを取得（画像出力）
	$yesterday = outhtml($yesterday, $yes_path);
	$today = outhtml($today, $day_path);
	$total = outhtml($total, $all_path);
}
/*use:\<\? include("dcount.php");echo "昨日：$yesterday 今日：$today 合計：$total";\?\>*/
