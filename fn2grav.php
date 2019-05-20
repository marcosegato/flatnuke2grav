<?php

/**
 * Fn2Grav
 *
 * This utility helps you to convert some Flatnuke contents to Grav.
 * Usage:
 * 	1. Configure the script filling your specific information if needed
 * 	2. Copy the script into root installation directory of Flatnuke
 * 	3. Execute the script from your web browser
 * 	4. Move '$gravnews_outpath' content from Flatnuke path to '[Grav_path]/user/pages/XX.blog' directory
 *
 * @author Marco Segato https://github.com/marcosegato
 * @version 20190324
 * @license https://opensource.org/licenses/GPL-2.0 GNU General Public License version 2
 *
 */

// -->> ==============================================
// -->> YOUR CUSTOM PARAMETERIZATION
// -->> ==============================================

$flatnuke_baseurl = "my.website.ext";                                       // basic URL of your Flatnuke installation (do NOT specify 'http/https' nor 'www')
$flatnuke_inpath  = "none_News";                                            // Flatnuke section containing the news you want to move to Grav (none_News is the default choice)
$gravnews_outpath = "./sections/$flatnuke_inpath/none_newsdata_grav";       // output path where Grav's new files will be created


// -->> ==============================================
// -->> START (DO NOT CHANGE ANYTHING UNDER THIS LINE)
// -->> ==============================================

// import Flatnuke APIs

include_once "functions.php";
include_once "languages/en.php";
create_fn_constants();
get_fn_version();
include "config.php";

// Welcome message

echo "<p><h1>Fn2Grav</h1></p>";
echo "<p>This utility helps you to convert some Flatnuke contents to Grav.</p>";
echo "<p><h2>Main information:</h2></p>";
echo "<ul>";
echo "<li>Flatnuke v." . FN_VERSION . "</li>";
echo "<li>Flatnuke base URL: " . $flatnuke_baseurl . "</li>";
echo "<li>Input path (Flatnuke): " . "./sections/$flatnuke_inpath/none_newsdata" . "</li>";
echo "<li>Output path (Grav): " . $gravnews_outpath . "</li>";
echo "<li>News' author: " . $admin . "</li>";
echo "</ul>";

// check Flatnuke min version before going on

if(FN_VERSION < "3.0.0") {
    echo "<p><h2><strong style='color:red'>SEVERE ERRROR</strong>: Script aborted, you need at least Flatnuke v.3.0.0 (you are actually using " . FN_VERSION . ")</h2></p>";
    exit();
}

// create and test output dir write permissions

if(!file_exists("$gravnews_outpath")) {
	if(!fn_mkdir("$gravnews_outpath", "w+")) {
		echo "<p><h2><strong style='color:red'>SEVERE ERRROR</strong>: Script aborted, cannot create writable output directory $gravnews_outpath</h2></p>";
    	exit();
	}
}

// some working stuff

$gravnews_item    = "";
$gravnews_cntok   = 0;
$gravnews_cntwa   = 0;
$gravnews_cntno   = 0;
$fn_localfilename = "";
$link_array       = array();

$gravnews_txtmask = "---
title: '%%TITLE%%'
date: %%DATE%%
author: %%AUTHOR%%
body_classes: header-lite fullwidth blogstyling
taxonomy:
    category: %%CATEGORY%%
    tag: [%%TAGS%%]
---

%%HEADER%%

";

// refresh Flatnuke news' list to prevent misalignements

save_news_list($flatnuke_inpath);

// main execution

foreach (load_news_list("none_News") as $mynews) {

    // check file existance: the list is stored in newslist.php file, but it could be not correct for any reason

    if(!file_exists("./sections/$flatnuke_inpath/none_newsdata/$mynews.fn.php")) {
		echo "<p>WARNING: Cannot find $mynews.fn.php file<p>";
		$gravnews_cntwa++;
        continue;
	}

	// create new directory for the current news

	if(!file_exists("$gravnews_outpath/$mynews")) {
        fn_mkdir("$gravnews_outpath/$mynews", "w+");
	}

	// read news data from XML file

    $newsdata = load_news("none_News", $mynews);

    //  convert header and body to HTML

    $newsdata['header'] = tag2html_migration($newsdata['header'], "home");
	$newsdata['body']   = tag2html_migration($newsdata['body'], "home");

	// pictures management

	$newsdata['header'] = html2grav_img($newsdata['header'], $mynews);
	$newsdata['body']   = html2grav_img($newsdata['body'], $mynews);

	// links management

	foreach(html2grav_local_links($newsdata['header'], $mynews) as $myarrayvalue) {
		if($myarrayvalue) {
			array_push($link_array, $myarrayvalue);
		}
	}

	foreach(html2grav_local_links($newsdata['body'], $mynews) as $myarrayvalue) {
		if($myarrayvalue) {
			array_push($link_array, $myarrayvalue);
		}
	}


    // fill in the mask that will build Grav's news file

    $gravnews_item = $gravnews_txtmask;

    $newsdata['title'] = str_replace("'", "", $newsdata['title']);
    $gravnews_item = str_replace('%%TITLE%%',    utf8_encode($newsdata['title']), $gravnews_item);
    $gravnews_item = str_replace('%%DATE%%',     date('H:i m/d/Y', $newsdata['date']), $gravnews_item);
    $gravnews_item = str_replace('%%AUTHOR%%',   $admin, $gravnews_item);
    //$gravnews_item = str_replace('%%CATEGORY%%', preg_replace("/[\.]*[a-z0-9]{1,4}$/i","",utf8_encode($newsdata['category'])), $gravnews_item);
    $gravnews_item = str_replace('%%CATEGORY%%', "blog", $gravnews_item);
    $gravnews_item = str_replace('%%TAGS%%',     utf8_encode(implode(', ', $newsdata['tags'])), $gravnews_item);
    $gravnews_item = str_replace('%%HEADER%%',   utf8_encode($newsdata['header']), $gravnews_item);
    if(trim($newsdata['body']) != "") {
        $gravnews_item .= "===\n\n" . utf8_encode($newsdata['body']) . "\n\n";
    }

    //echo "<pre>" . $gravnews_item . "</pre>";    // DEBUG

    // write output file

    fnwrite("$gravnews_outpath/$mynews/item.md", $gravnews_item, "w+", array("nonull"));

    // print conversion outcome for each file, and update counters

    if(file_exists("$gravnews_outpath/$mynews/item.md") AND filesize("$gravnews_outpath/$mynews/item.md")>0) {
        //echo "<p>INFO: File $mynews.fn.php has been imported<p>";     // DEBUG
        $gravnews_cntok++;
    } else {
        echo "<p>ERROR: Problem occurred when converting $mynews.fn.php file<p>";
        $gravnews_cntno++;
    }

}

// Final summary

echo "<p><h2>Runtime execution: " . date('d/m/Y H.i') . " - <input type='button' onclick='javascript:window.print()' value='Print results'/></h2></p>";
echo "<p><h3><u>News files</u></h3></p>";
echo "<p><strong style='color:green'>OK</strong>: $gravnews_cntok files succesfully converted</p>";
if($gravnews_cntwa > 0) {
	echo "<p><strong style='color:orange'>WARN</strong>: $gravnews_cntwa files not found</p>";
}
if(sizeof($link_array) > 0) {
	echo "<p><strong style='color:orange'>WARN</strong>: ".sizeof($link_array)." files with an internal link to manually fix:</p>";
	foreach($link_array as $link) {
		echo $link;
	}
}
echo "<p><strong style='color:red'>ERR</strong>: $gravnews_cntno files with errors</p>";
echo "<p><h2>Next steps:</h2></p>";
echo "<ol>";
echo "<li>Move '$gravnews_outpath' content into '[<i>Grav_path</i>]/user/pages/XX.blog' directory</li>";
if(sizeof($link_array) > 0) {
	echo "<li>Fix the links in the news listed above</li>";
}
if($gravnews_cntno > 0) {
	echo "<li>Severe errors need to be manually fixed</li>";
}
echo "</ol>";
echo "<p><h2>Remember to delete this script from your server!</h2></p>";


// -->> ==============================================
// -->> FUNCTIONS
// -->> ==============================================

/**
 * Convert HTML <IMG> tag to Markdown code.
 *
 * Extract all the pictures inserted in the news and:
 * 	1. convert code from HTML to Markdown
 * 	2. copy local image files to be used by Grav structure
 *
 * @param string $string String to verify
 * @param string $news Name of the news to work on
 * @return string Fixed text string
 *
 */
function html2grav_img($string, $news) {

	global $flatnuke_baseurl, $gravnews_outpath;

	// remove emoticons HTML code

	$string = preg_replace("/<img src=\'forum\/emoticon\/(.+?)\'.alt=\'(.*?)\' \/>/i","[$2]", $string);
	
	// remove target tag from links

	$string = preg_replace("/target.?=.?('|\").?blank_.?('|\")/","", $string);

	// convert HTML <IMG> tag to Markdown code when local images

	if(preg_match("/<img src=\"(.+?)\"(.*?)>/i", $string, $extract_image)) {

		// work only with local files or images linked to the current site URL

		if(!preg_match("/^http./", $extract_image[1]) OR preg_match("/".$flatnuke_baseurl."/", $extract_image[1])) {

			// get the right file path cleaning additional info

			$fn_localfilename = str_replace("gallery/thumb.php?image=", "", $extract_image[1]);				// remove gallery stuff
			$fn_localfilename = preg_replace("/&(.*)/i", "", $fn_localfilename);							// remove html extra stuff
			$fn_localfilename = preg_replace("/^http.(.*)".$flatnuke_baseurl."\//", "", $fn_localfilename);	// remove FN basic URL

			// create Grav's proper directory structure

			if(!file_exists("$gravnews_outpath/$news/assets")) {
				fn_mkdir("$gravnews_outpath/$news/assets", "w+");
			}

			// copy the image file from FN to Grav directory

			if(copy($fn_localfilename, "$gravnews_outpath/$news/assets/".basename($fn_localfilename))) {
				//echo "MOD News: $news - IMG: " . $fn_localfilename . " (copy_OK)<br/>";		// DEBUG
			} else {
				echo "MOD News: $news - IMG: " . $fn_localfilename . " (copy_ERR: Error in copying the picture file, please have a look to your web server log file)<br/>";
			}

			// remove links surrounding images and replace HTML with Markdown code

			$string = preg_replace('/<a.*?(<img.*?>)<\/a>/', '$1', $string);
			$string = str_replace($extract_image[0], "![](assets/".basename($fn_localfilename).")", $string);

		}
	}

	return $string;
}


/**
 * Get local links that must be manually fixed.
 *
 * Extract all the links found in a text string and:
 * 	1. get href tag value
 * 	2. build a list of links to fix
 *
 * @param string $string String to verify
 * @param string $news Name of the news to work on
 * @return array List of links to fix
 *
 */
function html2grav_local_links($string, $news) {

	global $flatnuke_baseurl;
	$link_list = array();

	// find all links

	if(preg_match_all("/(?i)<a([^>]+)>(.+?)<\/a>/", $string, $extract_link)) {

		foreach ($extract_link[0] as $mylink) {

			// test if local links or URLs pointing to the current site URL

			if(!preg_match("/(.*)(:\/\/|mailto:)/", $mylink) OR preg_match("/".$flatnuke_baseurl."/", $mylink)) {

				// get href tag value and build the final array

				if(preg_match("/href=[\"\']?([^\"\'>]+)[\"\']?/", $mylink, $match)) {
					array_push($link_list, "News: $news - Link: " . $match[1] . "<br/>");
				} else {
					array_push($link_list, "News: $news - Link: [empty or undefined href tag for this link!]<br/>");
				}

			}
		}

	}

	return $link_list;
}


/**
 * Gestione tag per codice html
 *
 * Rimpiazza i pseudo-tag ([b], [i], ecc) con i tag html).
 *
 * @author Simone Vellei <simone_vellei@users.sourceforge.net>
 * @author Marco Segato <segatom@users.sourceforge.net> | 20050916: Unificato la funzione per l'uso nel sito e nel forum
 *                                                      | 20190320: Modifiche per l'utilizzo della procedura in questo script
 * @since 2.5.7
 *
 * @param string $string Stringa da verificare
 * @param string $where Riferimento alla root per l'esecuzione del codice: 'home' per le news, 'forum' per il forum
 * @return string Codice HTML
 *
 */
function tag2html_migration($string, $where) {
	// verifico provenienza della chiamata e adatto i richiami alle directories
	$string=getparam($string,PAR_NULL,SAN_NULL);
	$where=getparam($where,PAR_NULL,SAN_FLAT);

	$prepath="forum/";
	// solo l'amministratore puo' usare codice HTML
	//da Flatnuke 3.0 la funzione tag2html viene usata in fase di visualizzazione
	//e non in fase di salvataggio. Dunque il controllo sul livello dell'utente
	//e sul codice da salvare avviene al di fuori di questa funzione
    $myforum=getparam("myforum", PAR_COOKIE, SAN_FLAT);

    // wrong encoded chars fixes
    $string = str_replace(chr(hexdec("C292")), "'", $string);   // MS Word's apostrophe

	// formatting fixes
	$string = str_replace("&lt;br/&gt;", "<br />", $string);
	$string = str_replace("&lt;br /&gt;", "<br />", $string);
	$string = str_replace("&lt;br&gt;", "<br />", $string);
	$string = str_replace("<br>", "<br />", $string);
	$string = str_replace("&#91;", "[", $string);
	$string = str_replace("&#93;", "]", $string);
	$string = str_replace("|", "", $string);
	// emoticons
	/*$string = str_replace("[:)]", "<img src='".$prepath."emoticon/01.png' alt=':)' />", $string);
	$string = str_replace("[:(]", "<img src='".$prepath."emoticon/02.png' alt=':(' />", $string);
	$string = str_replace("[:o]", "<img src='".$prepath."emoticon/03.png' alt=':o' />", $string);
	$string = str_replace("[:p]", "<img src='".$prepath."emoticon/04.png' alt=':p' />", $string);
	$string = str_replace("[:D]", "<img src='".$prepath."emoticon/05.png' alt=':D' />", $string);
	$string = str_replace("[:!]", "<img src='".$prepath."emoticon/06.png' alt=':!' />", $string);
	$string = str_replace("[:O]", "<img src='".$prepath."emoticon/07.png' alt=':O' />", $string);
	$string = str_replace("[8)]", "<img src='".$prepath."emoticon/08.png' alt='8)' />", $string);
	$string = str_replace("[;)]", "<img src='".$prepath."emoticon/09.png' alt=';)' />", $string);
	$string = str_replace("[rolleyes]", "<img src='".$prepath."emoticon/rolleyes.png' alt=':rolleyes:' />", $string);
	$string = str_replace("[neutral]", "<img src='".$prepath."emoticon/neutral.png' alt=':|' />", $string);
	$string = str_replace("[:x]", "<img src='".$prepath."emoticon/mad.png' alt=':x' />", $string);
	$string = str_replace("[O:)]", "<img src='".$prepath."emoticon/angel.png' alt='O:)' />", $string);
	$string = str_replace("[whistle]", "<img src='".$prepath."emoticon/whistle.png' alt='whistle' />", $string);
	$string = str_replace("[eh]", "<img src='".$prepath."emoticon/eh.png' alt='eh' />", $string);
	$string = str_replace("[evil]", "<img src='".$prepath."emoticon/evil.png' alt=':evil:' />", $string);
	$string = str_replace("[idea]", "<img src='".$prepath."emoticon/idea.png' alt=':idea:' />", $string);
	$string = str_replace("[bier]", "<img src='".$prepath."emoticon/bier.png' alt=':bier:' />", $string);
	$string = str_replace("[flower]", "<img src='".$prepath."emoticon/flower.png' alt=':flower:' />", $string);
	$string = str_replace("[sboing]", "<img src='".$prepath."emoticon/sboing.png' alt=':sboing:' />", $string);*/

	// formattazione testo
	$string = str_replace("\n", "<br />", $string);
	$string = str_replace("\r", "", $string);
	$string = str_replace("[b]", "<b>", $string);
	$string = str_replace("[u]", "<u>", $string);
	$string = str_replace("[/u]", "</u>", $string);
	$string = str_replace("[/b]", "</b>", $string);
	$string = str_replace("[i]", "<i>", $string);
	$string = str_replace("[/i]", "</i>", $string);
	$string = str_replace("[strike]","<span style=\"text-decoration : line-through;\">",$string);
	$string = str_replace("[/strike]","</span>",$string);
	$string = preg_replace("/\[quote\=(.+?)\]/s",'<blockquote><b><a href="index.php?mod=none_Login&amp;action=viewprofile&amp;user=$1" title="'._VIEW_USERPROFILE.'">$1</a> '._HASCRITTO.':</b><br />',$string);
	$string = str_replace("[quote]", "<blockquote>", $string);
	$string = str_replace("[/quote]", "</blockquote>", $string);
	$string = str_replace("[code]", "<pre>", $string);
	$string = str_replace("[/code]", "</pre>", $string);
	$string = preg_replace("/\[url\=(.+?)\](.+?)\[\/url\]/s",'<a title="$2" href="$1" target="blank_">$2</a>',$string);

	//if (_FN_IS_GUEST){
	//	$string = preg_replace("/\[mail\].*\[\/mail\]/i","<span style=\"text-decoration : line-through;\" title=\"only users can view mail addresses\">[e-mail]</span>",$string);
	//}
	//else {
		$string = preg_replace("/\[mail\](.+?)\[\/mail\]/s",'<a title="mail to $1" href="mailto:$1">$1</a>',$string);
	//}

	// immagini
	if(preg_match("/\[img\](.*?)\[\/img\]/s", $string, $img_match)>0) {
		if(preg_match("/(\.php|\.js)/i",$img_match[1])) {
			$string = preg_replace("/\[img\](.*?)\[\/img\]/s","",$string);
		} else {
			//if(@getimagesize($img_match[1])!=FALSE) {
				$string = str_replace("[img]", "<br /><img src=\"", $string);
				$string = str_replace("[/img]", "\" alt=\"uploaded_image\" /><br />", $string);
			//} else $string = preg_replace("/\[img\](.*?)\[\/img\]/s","",$string);
		}
	}

	//posizione
	$string = str_replace("[left]", "<div style=\"text-align : left;\">", $string);
	$string = str_replace("[right]", "<div style=\"text-align : right;\">", $string);
	$string = str_replace("[center]", "<div style=\"text-align : center;\">", $string);
	$string = str_replace("[justify]", "<div style=\"text-align : justify;\">", $string);
	$string = str_replace("[/left]", "</div>", $string);
	$string = str_replace("[/right]", "</div>", $string);
	$string = str_replace("[/center]", "</div>", $string);
	$string = str_replace("[/justify]", "</div>", $string);

	// colori del testo
	$string = str_replace("[red]", "<span style=\"color : #ff0000\">", $string);
	$string = str_replace("[green]", "<span style=\"color : #00ff00\">", $string);
	$string = str_replace("[blue]", "<span style=\"color : #0000ff\">", $string);
	$string = str_replace("[pink]", "<span style=\"color : #ff00ff\">", $string);
	$string = str_replace("[yellow]", "<span style=\"color : #ffff00\">", $string);
	$string = str_replace("[cyan]", "<span style=\"color : #00ffff\">", $string);
	$string = str_replace("[/red]", "</span>", $string);
	$string = str_replace("[/blue]", "</span>", $string);
	$string = str_replace("[/green]", "</span>", $string);
	$string = str_replace("[/pink]", "</span>", $string);
	$string = str_replace("[/yellow]", "</span>", $string);
	$string = str_replace("[/cyan]", "</span>", $string);

	//dimensione
	$string = str_replace("[size=50%]", "<span style=\"font-size: 50%;\">", $string);
	$string = str_replace("[size=75%]", "<span style=\"font-size: 75%;\">", $string);
	$string = str_replace("[size=100%]", "<span style=\"font-size: 100%;\">", $string);
	$string = str_replace("[size=150%]", "<span style=\"font-size: 150%;\">", $string);
	$string = str_replace("[size=200%]", "<span style=\"font-size: 200%;\">", $string);
	$string = str_replace("[/size]", "</span>", $string);

	//elenchi
	$string = str_replace("[ol]<br />", "<ol>", $string);
	$string = str_replace("[ol]", "<ol>", $string);
	$string = str_replace("[/ol]", "</ol>", $string);
	$string = str_replace("[*]", "<li>", $string);
	//per risolvere il problema dell'"a capo"
	$string = preg_replace("/\[\/\*\]\<br \/>/i", "</li>", $string);
	$string = preg_replace("/\[\/\*\]\n/i", "</li>", $string);
	$string = str_replace("[/*]", "</li>", $string);
	$string = str_replace("[ul]<br />", "<ul>", $string);
	$string = str_replace("[ul]", "<ul>", $string);
	$string = str_replace("[/ul]", "</ul>", $string);

	// WIKIPEDIA
	$items = explode("[/wp]",$string);
	for ($i = 0; $i < count($items); $i++) {
		$wp="";
		if(stristr($items[$i],"[wp")){
			$wp_lang = preg_replace("/.*\[wp lang=/","",$items[$i]);
			$wp_lang = preg_replace("/\].*/","",$wp_lang);
			$wp = preg_replace("/.*\[wp.*\]/", "", $items[$i]);
			$wp = preg_replace("/\[\/wp\].*/", "", $wp);
			if ($wp != "") {
				$nuovowp="<a style=\"text-decoration: none; border-bottom: 1px dashed; color: blue;\" target=\"new\" href=\"http://$wp_lang.wikipedia.org/wiki/$wp\">$wp</a>";
			$string=str_replace("[wp lang=$wp_lang]".$wp."[/wp]", $nuovowp, $string);
			}
		}
	}

	$items = "";
	// URLs
	$items = explode("[/url]",$string);
	for ($i = 0; $i < count($items); $i++) {
		$url="";
		if(stristr($items[$i],"[url]")){
			$url = preg_replace("/.*\[url\]/", "", $items[$i]);
			$url = preg_replace("/\[\/url\].*/", "", $url);
			if ($url != "") {
				if (stristr($url, "http://") == FALSE) {
					$nuovourl="<a target=\"new\" href=\"http://$url\">$url</a>";
				} else {
					$nuovourl="<a target=\"new\" href=\"$url\">$url</a>";
				}
			$string=str_replace("[url]".$url."[/url]", $nuovourl, $string);
			}
		}
	}

	$items = "";
	// youtube
	$items = explode("[/youtube]",$string);
	for ($i = 0; $i < count($items); $i++) {
		$url="";
		if(stristr($items[$i],"[youtube]")){
			$url = preg_replace("/.*\[youtube\]/", "", $items[$i]);
			$url = preg_replace("/\[\/youtube\].*/", "", $url);
			if ($url != "") {
				if (stristr($url, "youtube.com") == FALSE) {
					continue;
				} else {
					$link = preg_replace("/.+?youtube.com.+v=([a-zA-Z0-9]*).*/s",'<iframe class="youtube-player" width="430" height="259" src="http://www.youtube.com/embed/$1" frameborder="0"></iframe>',$url);
				}
				$string=str_replace("[youtube]".$url."[/youtube]", $link, $string);
			}
		}
	}

	return ($string);
}

?>
