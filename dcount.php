<?php
class counter
{
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
	protected $counter_mode     = 0;
	// 昨日カウント用GIF画像のディレクトリ
	protected $yes_path = './img/cntimg2/';
	// 本日カウント用GIF画像のディレクトリ
	protected $day_path = './img/cntimg2/';
	// 総カウント用GIF画像のディレクトリ
	protected $all_path = './img/cntimg2/';
	// カウンタ記録ファイル
	protected $log     = './all.dat';
	// 昨日カウント数の桁数
	protected $fig1    = 2;
	// 本日カウント数の桁数
	protected $fig2    = 2;
	// 合計カウント数の桁数
	protected $fig3    = 3;
	// 連続IPはカウントしない（yes=1 no=0)
	protected $ipcheck = 1;

	protected $yesterday = 0;
	protected $today     = 0;
	protected $total     = 0;

	protected $ip		 = '';
	protected $blacklist_useragent = array(
		'bot',
		'crawler',
		'spider',
		'archiver',
		'transcoder',
		'slurp',
		'search',
		'seek',
		'python',
		'java',
		'libwww',
		'curl',
		'wget',
	);

	//---------設定ここまで------
	// コンストラクタ
	public function __construct()
	{
		// UserAgentチェック
		$user_agent = getenv('HTTP_USER_AGENT');
		foreach ($this->blacklist_useragent as $ua) {
			if (stripos($user_agent, $ua) !== false) {
				// ブラックリストに一致したらカウンタを無効化
				return;
			}
		}
		$this->ip = getenv('REMOTE_ADDR');
		$this->countup();
	}

	//カウント数とパスを与えて、IMGタグを返す
	protected function outhtml($f_cnt, $c_path)
	{
		$i_tag = "";
		$size = getimagesize($c_path . "0.gif");  //0.gifからwidthとheight取得
		for ($i = 0; $i < strlen($f_cnt); $i++) {
			//桁数分だけループ
			$n = substr($f_cnt, $i, 1);			//左から一桁ずつ取得
			$i_tag .= "<IMG SRC=\"$c_path$n.gif\" alt=$n $size[3]>";
		}

		return $i_tag;
	}

	//カウントアップ
	public function countup()
	{
		$now_date = date("Ymd");	// 今日の日付
		$yes_date = date("Ymd", time() - 86400); // 昨日の日付

		// ファイルを配列に
		$dat = file($this->log);
		if (!$dat) {
			file_put_contents($this->log, "$now_date,0,1,1,$this->ip\n");
			$dat = file($this->log);
		}
		if (count($dat) > 1000) {
			//データが1000行を超えたら古いのを削除
			array_shift($dat);
		}

		$exists = false;

		foreach ($dat as $i => $line) {
			list($date, $yesterday_count, $today_count, $all_count, $addr) = explode(",", $line); //データを分解
			$addr = trim($addr); //改行コード除去
			// 連続IPはカウントしない場合
			if ($this->ipcheck) {
				// 同じIPが存在し、かつ日付が今日なら存在フラグを立てる
				if ("$addr" == $this->ip && $date == $now_date) {
					$exists = true;
				}
			}
		}

		// 最後のデータが昨日の日付かどうか
		if ($date == $now_date) {
			if (!$exists) {
				$today_count++; // 連続IPでなければ今日を1増やす
				$all_count++; 	// 合計をｶｳﾝﾄｱｯﾌﾟ
				//新しいデータを配列の最後に追加
				//日付|昨日|今日|合計|IP
				$new = implode(",", array($now_date, $yesterday_count, $today_count, $all_count, $this->ip)) . "\n"; //データ連結
				$dat[] = $new;
				file_put_contents($this->log, $dat);
			}
		} else {
			$yesterday_count = ($date == $yes_date) ? $today_count : 0; //昨日が今日なら今日を昨日に格納、違うなら0
			$today_count 	 = 1; //今日を1に
			$all_count 	 	 = $all_count + 1; //合計を1
			//新しいデータを配列の最後に追加
			//日付|昨日|今日|合計|IP
			$new = implode(",", array($now_date, $yesterday_count, $today_count, $all_count, $this->ip)) . "\n"; //データ連結
			$dat[] = $new;
			file_put_contents($this->log, $dat);
		}

		//カウント整形（空いた桁を0で埋める）
		$this->yesterday 	 = sprintf("%0" . $this->fig1 . "d", $yesterday_count);
		$this->today 		 = sprintf("%0" . $this->fig2 . "d", $today_count);
		$this->total 		 = sprintf("%0" . $this->fig3 . "d", $all_count);

		if ($this->counter_mode) {
			//タグを取得（画像出力）
			$this->yesterday = $this->outhtml($this->yesterday, $this->yes_path);
			$this->today 	 = $this->outhtml($this->today, $this->day_path);
			$this->total 	 = $this->outhtml($this->total, $this->all_path);
		}
		return;
	}

	public function echo_count($format)
	{
		$result = str_replace(
			array('{yesterday}', '{today}', '{total}'),
			array($this->yesterday, $this->today, $this->total),
			$format
		);
		echo $result;
	}

	/*use:\<\? include("dcount.php");echo "昨日：$yesterday 今日：$today 合計：$total";\?\>*/
}
$counter = new counter();
