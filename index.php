<?php
require "secrets.php";

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
	http_response_code(412);
	die("Who are you? (no user agent)");
}

$banned = [
	// Ser ut til å overvåke oppetid på HS-er, kanskje for korrelering med
	// relays.
	"Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.101 Safari/537.36" => "just_stop",
	// Jævla idiot indekserer dataurler.
	"Python-urllib/1.17" => "Do_not_follow_dataurls_and_use_descriptive_UA.",
];

if (isset($banned[$_SERVER['HTTP_USER_AGENT']])) {
	header('Location: http://fuckthefuckoffok.onion/' . $banned[$_SERVER['HTTP_USER_AGENT']], true, 301);
	exit;
}

// Rediriger til kulere domenenavn.
if (substr($_SERVER['HTTP_HOST'], 0, 16) === 'zdasaqqvbij5lahr') {
	header('Location: //loekchan3kvr6cw6' .
		substr($_SERVER['HTTP_HOST'], 16) .
		$_SERVER['REQUEST_URI'], true, 301);
	exit;
}

ob_start('ob_gzhandler');
error_reporting(E_ALL);

define('PER_PAGE', 10);
define('FROM_POST', 5);
define('MAX_FILE_SIZE', 32*1024*1024);

function error($msg="Du gjorde noe galt.", $code=500) {
	http_response_code($code);
	?><!doctype html><meta charset=utf8><title>Løkchan - feilmelding</title><style>html{background-color:beige;color:black}</style><?php
	echo "<h1>$msg</h1>";
	exit;
}
function humansize($n) {
	$suffix = "B";
	if ($n > 1024) {
		$n /= 1024;
		$suffix = "KiB";
	}
	if ($n > 1024) {
		$n /= 1024;
		$suffix = "MiB";
	}
	return sprintf("%d%s", $n, $suffix);
}
function cleanqval($v) {
	$out = "";
	foreach (str_split(substr($v, 0, 50)) as $x) {
		if (ord($x) <= 31 || ord($x) == 127) {
		} elseif ($x == '"') {
			$out .= '\\"';
		} else {
			$out .= $x;
		}
	}
	return $out;
}
function cleanmime($v) {
	if (preg_match("/^[a-z.-]*\/[a-z0-9.-]*$/i", $v)) {
		return strtolower($v);
	} else {
		return "application/octet-stream";
	}
}

$boards = [
	"alle" => "Alle i en.",
		"/00" => "", // Misc:
	"b" => "Diverse og tilfeldig.",
	"n" => "Nyheter.",
		"/10" => "", // Temaer:
	"pol" => "Politisk Ukorrekt.",
	"fil" => "Filosofi og Religion.",
	"fit" => "Trening, helse og steroider.",
	"fin" => "Finans og skatteunndragelse.",
	"lit" => "Litteratur.",
	"rus" => "Rusmidler.",
	"prog" => "Cypherpønker skriver kode.",
	"it" => "Annen informasjonsteknologi.",
	"hjelp" => "Psykologer, eksperter, støttegrupper og gruppeklemmer.",
		"/20" => "", // E-tjenester
	"cy" => "Cyberbataljonen - Angrep på mål og faenskap.",
	"et" => "Etterretningstjenesten.",
	"sik" => "Sikkerhet og Kontraetterretning.",
		"/30" => "", // IRL-orienterte greier.
	"lf" => "Det Frie Marked.",
	"t" => "Tjenester.",
	"k" => "Kriminalitet.",
	"mot" => "Motstandskamp.",
	"møt" => "Treff undercover politimenn.",
		"/40" => "", // Pene bilder.
	"porr" => "Til døden skiller oss ad.", // Obligatorisk.
	"j" => "Jenter.",
	"g" => "Gutter.",
		"/50" => "",
	"løk" => "Andre kule ting på løknettet.",
	"meta" => "Spørsmål om løkchan, forslag, etc.",
];
$quotes = [
	"«Men de feige, de vantro og vanhellige, de som myrder og som driver hor, trollmenn og avgudsdyrkere og alle løgnere, deres plass skal være i sjøen som brenner med ild og svovel. Det er den annen død.»<br>– Johannes' åpenbaring 21,8.",
];
$quotes_humm = [
	"«Men fordi hun husket sin ungdoms dager, da hun drev hor i Egypt, horet hun mer og mer. Hun lengtet etter beilerne sine, med lem som esler og sprut som hester.»<br>– Esekiel 23,19-20",
	"«Du tenkte på lengsel etter din skamløse ungdom, på det egypterne gjorde med brystene dine, de unge brystene.»<br>– Esekiel 23,21",
	"For elskhugen din<br>ingen mann skal<br>gjeva deg last og lyte.<br>Ofte klok mann fell,<br>der fåmingen stend,<br>for eit ovfagert andlet.",
];
$quotes_porr = array_merge($quotes_humm, [
	"«Begge var nakne, både mannen og kvinnen, og de skammet seg ikke for hverandre.»<br>– Første Mosebok 2,25"
]);
$quotes_j = array_merge($quotes_humm, [
	"«Hver av dere skal vite å vinne sin egen kone i hellighet og ære.»<br>– Paulus' første brev til tessalonikerne 4,4",
	"«Duften av oljene dine er deilig,<br>navnet ditt øses ut som olje.<br>Derfor liker jentene deg.»<br>– Høysangen 1,3",
]);
$quotes_lit_fil = [
	"«Men av treet til kunnskap om godt og ondt må du ikke spise. For den dagen du spiser av det, skal du dø.»<br>– Første Mosebok 2,17",
];

$board_quotes = [
	"fit" => [
		"«Da jeg var barn, talte jeg som et barn, tenkte jeg som et barn, forsto jeg som et barn. Men da jeg ble voksen, la jeg av det barnslige, og begynte å ta jævlig tunge markløft.»<br>– Paulus' første brev til korinterne 13,11",
		"«Slik jern kvesser jern skal et menneske kvesse et annet.»<br>– Salomos ordspråk 27,17",
		"«Duften av oljene dine er deilig<br>navnet ditt øses ut som olje.<br>Derfor liker jentene deg.»<br>– Høysangen 1,3",
	],
	"j" => $quotes_j,
	"porr" => $quotes_porr,
	"g" => [
		"«På samme måte sluttet mennene å ha naturlig samliv med kvinner og brant i begjær etter hverandre. Menn drev utukt med menn, og de måtte selv ta straffen for sin villfarelse»<br>– Paulus' brev til romerne 1,27",
	],
	"lit" => $quotes_lit_fil,
	"fil" => $quotes_lit_fil,
	"fin" => [
		"«Familie, religion, vennskap. Disse er de tre demonene du må slakte hvis du ønsker å lykkes innen business.»<br>– Montgomery Burns",
	],
	"pol" => [
		"Aldri du leggje<br>andre til last<br>det som mang ein mann hender.",
	],
	"rus" => [
		"«Vær edru og våk! Deres motstander, djevelen, går omkring som en brølende løve for å finne noen å sluke.»<br>– Peters første brev 5,8",
	],
	"hjelp" => [
		"«Men om du søker Gud og ber Den veldige om nåde, om du er ren og rettskaffen, så vil han våke over deg og gjenreise din rettferds bolig.»<br>– Job 8,5-6",
		"Vitlaus mann<br>vaker all natti<br>tenkjer både opp og ut.<br>Han er trøytt og mod<br>når morgonen kjem,<br>og alt er flokut som fyrr.",
		"«I stedet for å lure på når den neste ferien din er burde du kanskje ordne deg et liv du ikke trenger å slippe unna fra.»<br>– Seth Godin",
		"Ikke ofre det du vil ha mest for det du vil ha nå.",
	],
	"cy" => [
		"«Svovel og salt, hele landet avsvidd så det ikke kan sås, bli grønt eller noe strå kan vokse der, slik det også var da Sodoma og Gomorra, Adma og Sebojim ble ødelagt, de som Herren i sin vrede og harme ødela.»<br>– Femte Mosebok 29,23",
		"Våpni sine<br>skal mann på vollen<br>ikkje gange eit fet ifrå.<br>Uvisst er å vita<br>når på vegom ute<br>det spyrjast kann etter spjot.",
	],
	"sik" => [
		"«Behandle laptoppen din som du behandler tenåringsrømlingen i sex-kjelleren din: lås den ned når du går fra den.»<br>– the grugq",
	],
	"mot" => [
		"Våpni sine<br>skal mann på vollen<br>ikkje gange eit fet ifrå.<br>Uvisst er å vita<br>når på vegom ute<br>det spyrjast kann etter spjot.",
		"«Krig er en stygg ting, men ikke den styggeste av ting. Den nedbrutte og vanærende tilstanden av moralsk og patriotisk forfall som synes at ingen ting er verdt å krige for er mye verre. En person som har intet han er villig til å kjempe for, ingen ting som er viktigere for ham enn hans egen personlige sikkerhet, er en miserabel skapning, og har ingen sjanse til å være fri med mindre han holdes slik av anstrengelsene til bedre menn enn ham selv.»<br>– John Stuart Mill",
		"«Når de sier: ‘Fred og ingen fare’, da kommer plutselig undergangen over dem, brått som riene over en kvinne som skal føde. Og de kan ikke slippe unna.»<br>– Paulus' første brev til tessalonikerne 5,3",
		"«Slutten på alle ting er nær. Vær derfor sindige og edru, så dere kan be.»<br>– Peters første brev 4,7»",
		"«Og jeg så et dyr stige opp av havet. Det hadde ti horn og sju hoder og ti kroner på hornene, og på hodene sto det navn som var en spott mot Gud.»<br>– Johannes' åpenbaring 13,1",
	],
	"lf" => [
		"«Det tvinger alle – små og store, rike og fattige, frie og slaver – til å ha et merke på sin høyre hånd eller på pannen. Og ingen kan kjøpe eller selge noe uten å ha dette merket: dyrets navn eller det tall som svarer til navnet.»<br>– Johannes' åpenbaring 13,16-17",
	],
];
$board_quotes['alle'] = [];
foreach ($board_quotes as $k => $v) {
	$board_quotes['alle'] = array_merge(
		$board_quotes['alle'], $board_quotes[$k]
	);
}
$board_quotes['alle'] = array_unique($board_quotes['alle']);

$board = !empty($_GET['board']) ? $_GET['board'] : "alle";
$threadid = !empty($_GET['thread']) ? ((int) $_GET['thread']) : 0;
$file = !empty($_GET['file']) ? ((int) $_GET['file']) : 0;
$page = !empty($_GET['page']) ? ((int) $_GET['page']) : 0;

// /favicon\.ico -> index.php?favicon
// /([^/]+)/ -> index.php?board=$1
// /([^/]+)/([0-9]+) -> index.php?board=$1&page=$2
// /([^/]+)/src/([0-9]+) -> index.php?board=$1&thread=$2
// /([^/])/f/([0-9]+) -> index.php?board=$1&file=$2

if ($board === "bl") { // Fatter ikke hvorfor folk prøver dette.
	error("Yes, this is Dog. How may I direct your call?", 418);
}

if (!isset($boards[$board])) {
	error("Board not found.", 404);
}

if (isset($board_quotes[$board])) {
	$quotes = array_merge($quotes, $board_quotes[$board]);
}

if (isset($_GET['favicon'])) {
	header("Content-Type: image/x-icon");
	header("Cache-Control: public, max-age=1073741824");
	echo base64_decode(<<<EOD
AAABAAEAEBAAAAAAAABoBQAAFgAAACgAAAAQAAAAIAAAAAEACAAAAAAAAAEAAAAAAAAAAAAAAAEA
AAAAAAAAAAAAh6XJAP///wCFhYUAcsz8AFlZ/wBskLoAh4f6AMnd9QArLC4ApL7bAJa01gCSrfAA
us/oAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAYGBgYGBgYGAAAAAAAAAAYLCwMDAwMLCwYAAAAAAAYLCgMICAgNAwoLBgAAAAAGCwgD
CAwMCAMICwYAAAAABgsKDQ0ICAgNCgsGAAAAAAYLCwEJCAgJAQsLBgAAAAAABgsBAQ0NAQELBgAA
AAAAAAAGCwsNDQULBgAAAAAAAAAAAAYLCgUHBQAAAAAAAAAAAAAABgUHBAcFAAAAAAAAAAAAAAYL
BQcFAAAAAAAAAAICAgYLBgAFAAAAAAAAAAICAAIGBgAAAAAAAAAAAAACAAAAAAAAAAAAAAAAAAAA
AgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAPAPAADgBwAAwAMAAMADAADAAwAAwAMAAOAH
AADwDwAA+B8AAPwPAAD8HwAAwL8AAJH/AAC//wAAv/8AAP//AAA=
EOD
);
	exit;
}

pg_connect("dbname=loekchan user=loekchan")
	or error("MySQL Connection Error");

if ($file !== 0) {
	$res = pg_query("select filemime, filename, attachment from posts where postid = $file");
	list($type, $name, $att) = pg_fetch_row($res);
	$att = pg_unescape_bytea($att);
	if ($att === null) {
		error("Ingen fil her.", 404);
	}
	if ($type === "text/plain") {
		$type = $type."; charset=".mb_detect_encoding($att);
	}
	header("Content-Type: ".$type);
	header("Content-Disposition: inline; filename=\"" . cleanqval($name) . "\"");
	header("Cache-Control: public, max-age=1073741824");
	echo $att;
	exit;
}

$reply = $threadid !== 0;
if ($reply) {
	$res = pg_query("select * from threads natural join posts where postid = $threadid");
	$thread = pg_fetch_assoc($res);
	if (empty($thread)) {
		error("fuk of u fgt", 451);
	}

	$res = pg_query("select postid, post from posts where postid = (select min(postid) from posts where threadid = {$thread['threadid']})");
	list($postid, $startpost) = pg_fetch_row($res);
	if ($postid === null) {
		mail(ADMIN_MAILADDR, "Løkchanfail", "Du fucka noe opp. Sjekk $threadid");
		error("Ukonsistente databasetabeller oppdaget.");
	}

	if (!isset($_POST['post'])) {
		if ($board != $thread['board'] || $threadid != $postid) {
			header("Location: /{$thread['board']}/src/$postid", true, 301);
			exit;
		}
	}
	$threadid = ((int) $thread['threadid']);
	$locked = $thread['locked'] === 't';
	if (empty(trim($startpost))) {
		$title = "/$board/ - Løkchan";
	} elseif (mb_strlen($startpost) > 20) {
		$title = htmlspecialchars(mb_substr($startpost, 0, 20)) . "… - /$board/ - Løkchan";
	} else {
		$title = htmlspecialchars($startpost) . " - /$board/ - Løkchan";
	}
} else {
	$thread = [];
	$locked = false;
	$title = "/$board/ - Løkchan";
}

if (($board === "alle" && $reply) || isset($_GET['error'])) {
	error();
}

if (isset($_POST['post'])) {
	pg_query("BEGIN");
	if ($board === "alle") {
		error();
	}
	if (!$reply) {
		$res = pg_query("insert into threads (board) VALUES ('$board') returning *");
		$thread = pg_fetch_assoc($res);
	}
	if ($thread['locked'] !== 'f') {
		error("Tråden er låst.", 403);
	}
	if (isset($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
		switch ($_FILES['file']['error']) {
		case UPLOAD_ERR_OK:
			break;
		case UPLOAD_ERR_INI_SIZE:
		case UPLOAD_ERR_FORM_SIZE:
			error("Fila di var for stor.", 413);
		case UPLOAD_ERR_PARTIAL:
			error("Ikke hele fila di ble opplastet.", 412);
		default:
			error();
		}
		switch ($_FILES['file']['type']) {
		case "image/gif":
			$filemime = "image/gif";
			$image = imagecreatefromgif($_FILES['file']['tmp_name']);
			$image or error("Kunne ikke lese bildet ditt.");
			$attachment = file_get_contents($_FILES['file']['tmp_name']);
			break;
		case "image/jpeg":
			$filemime = "image/jpeg";
			$image = imagecreatefromjpeg($_FILES['file']['tmp_name']);
			$image or error("Kunne ikke lese bildet ditt.");
			ob_start();
			imagejpeg($image, null, 85) or error();
			$attachment = ob_get_contents();
			ob_end_clean();
			break;
		case "image/png":
			$filemime = "image/png";
			$image = imagecreatefrompng($_FILES['file']['tmp_name']);
			$image or error("Kunne ikke lese bildet ditt.");
			ob_start();
			imagepng($image, null, 9, PNG_ALL_FILTERS) or error();
			$attachment = ob_get_contents();
			ob_end_clean();
			break;
		default:
			$filemime = cleanmime($_FILES['file']['type']);
			$image = false;
			$attachment = file_get_contents($_FILES['file']['tmp_name']);
		}
		if ($image) {
			$imagex = imagesx($image);
			$imagey = imagesy($image);
			$imagemax = $reply ? 125 : 250;
			if ($imagex > $imagey) {
				$newx = min($imagex, $imagemax);
				$newy = min($imagey, round($imagey/$imagex * $imagemax));
			} else {
				$newy = min($imagey, $imagemax);
				$newx = min($imagex, round($imagex/$imagey * $imagemax));
			}
			$thumbnail = imagecreatetruecolor($newx, $newy);
			imageantialias($thumbnail, true);
			imagecopyresampled($thumbnail, $image, 0,0,0,0, $newx, $newy, $imagex, $imagey);
			ob_start();
			imagejpeg($thumbnail, null, 75) or error();
			$thumbnail = ob_get_contents();
			ob_end_clean();
		} else {
			$imagex = null;
			$imagey = null;
			$thumbnail = null;
		}
		$threadid = ((int) $thread['threadid']);
		$name = !empty($_POST['name']) ? pg_escape_literal(trim($_POST['name'])) : "null";
		$mail = !empty($_POST['mail']) ? pg_escape_literal(trim($_POST['mail'])) : "null";
		$trip = !empty($_POST['trip']) ? ("'" . pg_escape_bytea(sha1(CHAN_SALT . trim($_POST['trip']), true)) . "'") : "null";
		$post = pg_escape_literal(str_replace("\r", "", $_POST['post']));
		$thumbnail = $thumbnail !== null ? "'" . pg_escape_bytea($thumbnail) . "'" : "null";
		$filemime = pg_escape_literal($filemime);
		$filename = pg_escape_literal($_FILES['file']['name']);
		$filesize = strlen($attachment);
		$imagex = $imagex !== null ? ((int) $imagex) : "null";
		$imagey = $imagey !== null ? ((int) $imagey) : "null";
		$filesha1 = "'" . pg_escape_bytea(sha1($attachment, true)) . "'";
		$attachment = "'" . pg_escape_bytea($attachment) . "'";

		$res = pg_query("select count(*) from posts where filesha1 = $filesha1");
		list($n) = pg_fetch_row($res);
		if ($n > 0) {
			error("Fila du forsøkte å laste opp finnes allerede på løkchan.", 409);
		}

		$res = pg_query("insert into posts (threadid, name, mail, trip, post, thumbnail, filemime, filename, " .
		                       "filesize, imagex, imagey, filesha1, attachment)" .
		                "values ($threadid, $name, $mail, $trip, $post, $thumbnail, $filemime, $filename, " .
		                        "$filesize, $imagex, $imagey, $filesha1, $attachment) returning postid;");
		list($postid) = pg_fetch_row($res);
	} else {
		if (empty(trim($_POST['post']))) {
			error("Du må poste noe.", 402);
		}
		$threadid = ((int) $thread['threadid']);
		$name = !empty($_POST['name']) ? pg_escape_literal(trim($_POST['name'])) : "null";
		$mail = !empty($_POST['mail']) ? pg_escape_literal(trim($_POST['mail'])) : "null";
		$trip = !empty($_POST['trip']) ? ("'" . pg_escape_bytea(sha1(CHAN_SALT . trim($_POST['trip']), true)) . "'") : "null";
		$post = pg_escape_literal(str_replace("\r", "", $_POST['post']));
		$res = pg_query("insert into posts (threadid, name, mail, trip, post) " .
		                "values ($threadid, $name, $mail, $trip, $post) returning postid");
		list($postid) = pg_fetch_row($res);
	}
	if ($mail !== "'sage'") {
		pg_query("update threads set modtime = now() where threadid = $threadid");
	}
	pg_query("COMMIT");
	header("Location: /$board/src/$postid", true, 303);
	exit;
}

?>
<!doctype html><meta charset=utf8><title><?= $title ?></title><style>html{background-color:beige;color:black;font-family:sans-serif}h1,th{font-family:serif}th{background-color:bisque}td,ul{text-align:left}.center{text-align:center;width:780px;margin:0 auto}.t{font-size:0.8em}table,textarea,input[type=text]{width:100%}.img{float:left;margin:3px 20px 5px}.name,.cur{color:green;font-weight:bold}.admin{color:red;font-weight:bold}.post{padding:4px;border:1px outset;background-color:bisque;display:table;margin:4px}.header{padding-bottom:2px}.q{color:rgb(120,153,34)}.txt{line-height:1.4em;margin: 0}</style><?php

?><script>document.addEventListener('DOMContentLoaded',function(){Array.prototype.forEach.call(document.querySelectorAll("img"),function(e){e.addEventListener("click",function(){var tmp=this.getAttribute('src');this.setAttribute('src',this.getAttribute('data-uri'));this.setAttribute('data-uri',tmp);});});});</script><?php

echo "<p>[/";
foreach ($boards as $k => $v) {
	if ($k[0] === "/") {
		echo "] [/";
		continue;
	}
	$v = htmlspecialchars($v);
	echo " <a href=/$k/ title=\"$v\">$k</a> /";
}
echo "]</p>";

?><div class=center><h1>Løkchan</h1><p>/<?= $board ?>/ - <?= htmlspecialchars($boards[$board]) ?><p class=t><?= $quotes[array_rand($quotes)]; ?></p><?php

if ($board !== "alle" && !$locked) {
	?><form enctype=multipart/form-data action="" method=post><table><tr><th colspan=6>Modus: <?= $reply ? "svar" : "ny tråd"; ?></th></tr><tr><th>Navn</th><td><input type=text name=name placeholder=Anonym></td><th>Mail</th><td><input type=text name=mail placeholder=age></td><th>Trip</th><td><input name=trip type=text placeholder="er for fags"></td></tr><tr><th>Post</th><td colspan=5><textarea name=post cols=80 rows=10></textarea></td></tr><tr><th>Fil</th><td colspan=5><input type=hidden name=MAX_FILE_SIZE value=<?= MAX_FILE_SIZE ?>><input type=file name=file></td></tr><tr><th colspan=6><input type=submit value="<?= $reply ? "Post et svar" : "Lag en ny tråd"; ?>"></th></tr></table></form><?php

	?><ul class=t><li>Ikke spam.<li>Ingen porno av prepubertale barn eller tortur av katter, please. Admin har sarte følelser.<li>Bryt gjerne alle andre lover.</ul><?php
}
echo "</div><hr>";

function print_thread($threadid, $board, $issticky, $locked, $isidx, $limit, $printboard) {
	$threadid = ((int) $threadid);
	echo "<div class=thread>";
	$res = pg_query("select count(*) from posts where threadid = $threadid");
	list($n) = pg_fetch_row($res);

	$res = pg_query("select postid, createtime, name, mail, trip, " .
	                        "post, thumbnail, filemime, filename, " .
	                        "filesize, imagex, imagey " .
	                "from posts where threadid = $threadid " .
	                "order by createtime asc");
	$isfirst = true;
	$rows = [];
	while ($row = pg_fetch_assoc($res)) {
		$rows[$row['postid']] = $row;
	}
	$p = 0;
	$isfirst = true;
	$images = 0;
	$otherfiles = 0;
	foreach ($rows as $rowid => $row) {
		if ($isfirst) {
			$firstpost = $rowid;
		}
		if ($isfirst || $limit === false || $p > $n - $limit) {
			print_post(
				$row, $rows, $threadid, $board, $isfirst,
				$printboard, $isidx, $firstpost
			);
		} else {
			if ($row['thumbnail'] !== null) {
				++$images;
			} elseif ($row['filemime'] !== null) {
				++$otherfiles;
			}
		}
		if ($isidx && $n > $limit && $p == ($n - $limit)) {
			echo "<b>",
				($n - $limit),
				" post",
				($n - $limit > 1 ? "er" : "");
			if ($images === 0 xor $otherfiles === 0) {
				echo " og ";
			} elseif ($images > 0 && $otherfiles > 0) {
				echo ", ";
			}
			if ($images > 0) {
				if ($images === 1) {
					echo "ett bilde";
				} else {
					echo $images, " bilder";
				}
			}
			if ($images > 0 && $otherfiles > 0) {
				echo " og ";
			}
			if ($otherfiles > 0) {
				echo $otherfiles, " fil",
					($otherfiles > 1 ? "er" : "");
			}
			echo " utelatt.</b>";
		}
		++$p;
		$isfirst = false;
	}
	echo "</div><hr style=\"clear:both\">";
}
function print_post($post, $posts, $threadid, $board, $isfirst, $printboard,
	    $isidx, $firstpost) {
	extract($post);
	if ($trip !== null) {
		$trip = substr(base64_encode(pg_unescape_bytea($trip)), 0, 10);
	}
	echo "<div id=\"p$postid\" class=".($isfirst?"first":"")."post>";
	echo "<div class=header>";
	if ($isfirst && $printboard) {
		echo "<a href=\"/$board/\">/$board/</a> - ";
	}
	if (strtolower($name) === "admin" && $trip === "5SzhyzWI5o") {
		$nameclass = "admin";
	} else {
		$nameclass = "name";
	}
	if ($mail !== null && $mail !== "noko") {
		echo "<a href=\"mailto:",
			rawurlencode($mail),
			"\" class=$nameclass>";
	} else {
		echo "<span class=$nameclass>";
	}
	echo htmlspecialchars($name === null ? "Anonym" : $name);
	if ($mail !== null && $mail !== "noko") {
		echo "</a>";
	} else {
		echo "</span>";
	}
	if ($trip !== null) {
		echo "<span class=trip>!!", $trip, "</span>";
	}
	echo " <span class=time>",
		strftime("%F %R", strtotime($createtime)),
		"</span>";

	if ($filemime !== null) {
		if (mb_strlen($filename) > 30) {
			$filetitle = " title=\"".htmlspecialchars($filename)."\"";
			$filename = mb_substr($filename, 0, 27) . "…";
		} else {
			$filetitle = "";
		}
		echo " [<a href=\"/$board/f/$postid\" class=imgname$filetitle>",
			htmlspecialchars($filename),
			"</a>, ",
			"<span class=filemeta>",
			$filemime, ", ",
			($imagex !== null ? $imagex . "x" . $imagey . ", " : ""),
			humansize($filesize),
			"</span>]";
	}
	echo " No. " . $postid;
	if ($isfirst && $isidx) {
		echo " <a href=\"/$board/src/$postid\">[Svar]</a>";
	}
	echo "</div>"; // header
	if ($thumbnail !== null) {
		echo "<div class=img>",
			"<img data-uri=\"/$board/f/$postid\" alt=\"\" src=\"data:image/jpeg;base64,",
			base64_encode(pg_unescape_bytea($thumbnail)),
			"\"></div>";
	}
	echo "<p class=txt>";
	echo process_post($postid, $post, ($isidx?[]:$posts), $board, $isidx, $firstpost);
	echo "</p></div>";
}
function process_post($postid, $post, $posts, $board, $isidx, $firstpost) {
	$post = str_replace("\r", "", $post);
	if ($isidx) {
		$postlines = explode("\n", $post);
		if (count($postlines) > 10) {
			$n = array_sum(array_map(function ($x) { return mb_strlen($x); },
			                         array_slice($postlines, 0, 10))) + 9;
		} else {
			$n = 1000;
		}
		$npost = mb_substr($post, 0, min($n, 1000));
		if ($npost !== $post) {
			$npost .= "…";
			$post = htmlspecialchars($npost)."<br>";
			$post .= "<a href=/$board/src/$firstpost#p$postid>[Les resten]</a>";
		} else {
			$post = htmlspecialchars($npost);
		}
	} else {
		$post = htmlspecialchars($post);
	}
	$post = preg_replace("/&gt;&gt;&gt;\/(\w+)\/(?=\D|$)/", "<a href=\"/$1/\">&gt;&gt;&gt;/$1/</a>", $post);
	$post = str_replace("\n", "<br>", $post);
	$post = preg_replace("/<br>&gt;(.*)<br>/U", "<br><span class=q>&gt;$1</span><br>", $post);
	$post = preg_replace("/<br>&gt;(.*)<br>/U", "<br><span class=q>&gt;$1</span><br>", $post);
	$post = preg_replace("/^&gt;(.*)<br>/U", "<span class=q>&gt;$1</span><br>", $post);
	$post = preg_replace("/<br>&gt;(.*)$/U", "<br><span class=q>&gt;$1</span>", $post);
	$post = preg_replace_callback("/&gt;&gt;(&gt;\/\w+\/)?(\d+)/", function ($matches) use ($posts, $board) {
			if (isset($posts[$matches[2]])) {
				return "<a href=\"#p{$matches[2]}\">&gt;&gt;{$matches[2]}</a>";
			} else {
				$res = pg_query("select t.board, min(p.postid), op.post from threads t natural join posts p, (select threadid, post from threads natural join posts where postid=" .((int) $matches[2]).") op where op.threadid = t.threadid group by board, op.post");
				$n = pg_num_rows($res);
				if ($n == 0) {
					return $matches[0];
				}
				list($board_, $tid, $refdpost) = pg_fetch_row($res);
				$refdpost_ = mb_substr($refdpost, 0, 100);
				if ($refdpost !== $refdpost_) {
					$refdpost_ .= "…";
				}
				if (strlen($refdpost) > 0) {
					$refdpost = " title=\"".htmlspecialchars($refdpost_)."\"";
				}
				if ($board_ !== $board) {
					return "<a href=\"/$board_/src/$tid#p{$matches[2]}\"$refdpost>&gt;&gt;&gt;/$board_/{$matches[2]}</a>";
				} else {
					return "<a href=\"/$board/src/$tid#p{$matches[2]}\"$refdpost>&gt;&gt;{$matches[2]}</a>";
				}
			}
		}, $post);
	return $post;
}

if ($threadid === 0) {
	$res = pg_query("select count(*) from threads " .
	                ($board !== "alle" ? "where board = '$board' " : ""));
	list($num_threads) = pg_fetch_row($res);

	$res = pg_query("select threadid, board, issticky, locked from threads " .
	                ($board !== "alle" ? "where board = '$board' " : "") .
	                "order by " .
	                        ($board !== "alle" ? "issticky desc, " : "") .
	                        "modtime desc " .
	                "limit " . PER_PAGE . " offset " . ($page*PER_PAGE));
	$threads = [];
	while ($row = pg_fetch_assoc($res)) {
		$threads[$row['threadid']] =
			[ "board" => $row['board']
			, "issticky" => $row['issticky']
			, "locked" => $row['locked']
			];
	}
	if (empty($threads)) {
		die("Det er ingen ting her.");
	}
	$curboard = $board;
	?><div class=board><?php
		foreach ($threads as $threadid => $x) {
			extract($x);
			print_thread($threadid, $board, $issticky, $locked, true, FROM_POST, $curboard === "alle");
		}
	?></div><?php
	$pages = ceil($num_threads / PER_PAGE) - 1;
	if ($pages > 0) {
		if ($page != 0) {
			$prev = $page - 1;
			if ($prev == 0) $prev = "";
			echo "<a href=/$curboard/$prev>[Forrige]</a> ";
		}
		foreach (range(0,$pages) as $p) {
			echo "<a ".($page == $p ? "class=cur " : "")."href=/$curboard/".($p == 0 ? "" : $p ).">[$p]</a> ";
		}
		if ($page != $pages) {
			$next = $page + 1;
			echo "<a href=/$curboard/$next>[Neste]</a>";
		}
	}
} else {
	extract($thread);
	print_thread($threadid, $board, $issticky, $locked, false, false, false);
}
