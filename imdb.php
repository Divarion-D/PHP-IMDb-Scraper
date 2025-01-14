<?php
/////////////////////////////////////////////////////////////////////////////////////////////////////////
// Free PHP IMDb Scraper API for the new IMDb Template.
// Version: 4.4
// Author: Abhinay Rathore
// Website: http://www.AbhinayRathore.com
// Blog: http://web3o.blogspot.com
// Demo: http://lab.abhinayrathore.com/imdb/
// More Info: http://web3o.blogspot.com/2010/10/php-imdb-scraper-for-new-imdb-template.html
// Last Updated: May 6, 2014
/////////////////////////////////////////////////////////////////////////////////////////////////////////

class Imdb
{	
	// Get movie information by Movie Title.
	// This method searches the given title on Google, Bing or Ask to get the best possible match.
	public function getMovieInfo($title, $getExtraInfo = true)
	{
		$imdbId = $this->getIMDbIdFromSearch(trim($title));
		if($imdbId === NULL){
			$arr = array();
			$arr['error'] = "No Title found in Search Results!";
			return $arr;
		}
		return $this->getMovieInfoById($imdbId, $getExtraInfo);
	}

    // Get movie information by IMDb url.
    public function getMovieInfoByURL($url, $getExtraInfo = true)
    {
        $cleanedUrl = preg_replace('/(\?.*)/ms','', $url);
        if(!$this->endsWith($cleanedUrl, '/')) {
            $cleanedUrl .= '/';
        }
        return $this->scrapeMovieInfo($cleanedUrl, $getExtraInfo);
    }

	// Get movie information by IMDb Id.
	public function getMovieInfoById($imdbId, $getExtraInfo = true)
	{
		$imdbUrl = "https://www.imdb.com/title/" . trim($imdbId) . "/";
		return $this->scrapeMovieInfo($imdbUrl, $getExtraInfo);
	}
	
	// Scrape movie information from IMDb page and return results in an array.
	private function scrapeMovieInfo($imdbUrl, $getExtraInfo = true)
	{   $nameExtractor = '/<a.*?href="\/name\/(.*?)["|\/].*?>(.*?)<\/a>/ms';
		$arr = array();
		$html = $this->geturl("${imdbUrl}reference");
		
		$title_id = $this->match('/<link rel="canonical" href="https?:\/\/www.imdb.com\/title\/(tt\d+)\/reference" \/>/ms', $html, 1);
		if(empty($title_id) || !preg_match("/tt\d+/i", $title_id)) {
			$arr['error'] = "No Title found on IMDb!";
			return $arr;
		}
		
		$arr['title_id'] = $title_id;
		$arr['imdb_url'] = $imdbUrl;
		$arr['title'] = trim($this->match('/<meta name="title" content="(.*?)\(/ms', $html, 1));
		$arr['original_title'] = trim($this->match('/<meta property=\'og:title\' content="(.*?)\(/ms', $html, 1));
		$arr['year'] = trim($this->match('/<title>.*?\(.*?(\d{4}).*?\).*?<\/title>/ms', $html, 1));
		$arr['rating'] = $this->match('/<\/svg>.*?<\/span>.*?<span class="ipl-rating-star__rating">(.*?)<\/span>/ms', $html, 1);
		$arr['genres'] = $this->match_all('/<a.*?>(.*?)<\/a>/ms', $this->match('/Genres<\/td>.*?<td>(.*?)<\/td>/ms', $html, 1), 1);
		$arr['directors'] = array_values($this->match_all_key_value($nameExtractor, $this->match('/<h4 name="directors" id="directors" class="ipl-header__content ipl-list-title">.*?Directed by.*?<table(.*?)<\/table>/ms', $html, 1)));
		$arr['writers'] = array_values($this->match_all_key_value($nameExtractor, $this->match('/Writers:.*?<ul(.*?)<\/ul>/ms', $html, 1)));
		$arr['cast'] = $this->match_all_key_value('/itemprop="name">(.*?)<.*?<td class="character">.*?<div>(.*?)<\/div>/ms', $this->match('/<table class="cast_list">(.*?)<\/table>/ms', $html, 1));
		$arr['cast'] = array_keys(array_slice($arr['cast'], 0, 30));
		//$arr['cast'] = array_slice($arr['cast'], 0, 30);
		$arr['stars'] = array_values($this->match_all_key_value($nameExtractor, $this->match('/Stars:(.*?)<\/ul>/ms', $html, 1)));
		$arr['producers'] = array_values($this->match_all_key_value($nameExtractor, $this->match('/<h4 name="producers" id="producers" class="ipl-header__content ipl-list-title">.*?Produced by.*?<table(.*?)<\/table>/ms', $html, 1)));
		$arr['musicians'] = array_values($this->match_all_key_value($nameExtractor, $this->match('/Music by.*?<table(.*?)<\/table>/ms', $html, 1)));
		$arr['cinematographers'] = array_values($this->match_all_key_value($nameExtractor, $this->match('/Cinematography by.*?<table(.*?)<\/table>/ms', $html, 1)));
		$arr['editors'] = array_values($this->match_all_key_value($nameExtractor, $this->match('/Film Editing by.*?<table(.*?)<\/table>/ms', $html, 1)));
		$arr['mpaa_rating'] = $this->match('/<a href="\/preferences\/general" class=>Change View<\/a>.*?<\/span>.*?<hr>.*?<ul class="ipl-inline-list">.*?<li class="ipl-inline-list__item">.*?(G|PG-13|PG-14|PG|R|NC-17|X).*?<\/li>/ms', $html, 1);
		$arr['release_date'] = $this->match('/releaseinfo">([0-9][0-9]? ([a-zA-Z]*) (19|20)[0-9][0-9])/ms', $html, 1);
		$arr['tagline'] = trim(strip_tags($this->match('/Taglines<\/td>.*?<td>(.*?)</ms', $html, 1)));
		$arr['plot'] = trim(strip_tags($this->match('/Plot Summary<\/td>.*?<td>.*?<p>(.*?)</ms', $html, 1)));
		$arr['plot_keywords'] = $this->match_all('/<a.*?>(.*?)<\/a>/ms', $this->match('/Plot Keywords<\/td>.*?<td>.*?<ul class="ipl-inline-list">(.*?)<\/ul>/ms', $html, 1), 1);
		$arr['poster'] = $this->match('/class="titlereference-primary-image".*?src="(.*?)".*? \/>/ms', $html, 1);
		$arr['poster_large'] = "";
		$arr['poster_full'] = "";
		if ($arr['poster'] != '' && strpos($arr['poster'], "m.media-amazon.com") > 0) { //Get large and small posters
			$arr['poster'] = preg_replace('/_V1.*?.jpg/ms', "_V1._SY200.jpg", $arr['poster']);
			$arr['poster_large'] = preg_replace('/_V1.*?.jpg/ms', "_V1._SY500.jpg", $arr['poster']);
			$arr['poster_full'] = preg_replace('/_V1.*?.jpg/ms', "_V1._SY0.jpg", $arr['poster']);
		} else {
			$arr['poster'] = "";
		}
		$arr['runtime'] = trim($this->match('/Runtime<\/td>.*?(\d+) min.*?<\/li>/ms', $html, 1));
		$arr['top_250'] = trim($this->match('/Top 250: #(\d+)</ms', $html, 1));
		$arr['oscars'] = trim($this->match('/Won (\d+) Oscars?\./ms', $html, 1));
		if(empty($arr['oscars']) && preg_match("/Won Oscar\./i", $html)) $arr['oscars'] = "1";
		$arr['awards'] = trim($this->match('/(\d+) wins/ms',$html, 1));
		$arr['nominations'] = trim($this->match('/(\d+) nominations/ms',$html, 1));
		$arr['votes'] = $this->match('/<span class="ipl-rating-star__total-votes">\((.*?)\)<\/span>/ms', $html, 1);
		$arr['language'] = $this->match_all('/<a.*?>(.*?)<\/a>/ms', $this->match('/Language<\/td>(.*?)<\/td>/ms', $html, 1), 1);
        	$arr['country'] = $this->match_all('/<a.*?>(.*?)<\/a>/ms', $this->match('/Country<\/td>(.*?)<\/td>/ms', $html, 1), 1);
        
		if($getExtraInfo == true) {
			$plotPageHtml = $this->geturl("${imdbUrl}plotsummary");
			$arr['storyline'] = trim(strip_tags($this->match('/id="summary.*?">.*?<p>(.*?)(<|<\/p>)/ms', $plotPageHtml, 1)));
			if(empty($arr['plot'])) {
                $arr['plot'] = $arr['storyline'];
            }
			$releaseinfoHtml = $this->geturl("https://www.imdb.com/title/" . $arr['title_id'] . "/releaseinfo");
			$arr['also_known_as'] = $this->getAkaTitles($releaseinfoHtml);
			$arr['release_dates'] = $this->getReleaseDates($releaseinfoHtml);
			$arr['recommended_titles'] = $this->getRecommendedTitles($arr['title_id']);
			$arr['media_images'] = $this->getMediaImages($arr['title_id']);
			$arr['videos'] = $this->getVideos($arr['title_id']);
		}
		return $arr;
	}
	
	// Scan all Release Dates.
	private function getReleaseDates($html){
		$releaseDates = array();
		foreach($this->match_all('/<tr.*?>(.*?)<\/tr>/ms', $this->match('/<table id="release_dates".*?>(.*?)<\/table>/ms', $html, 1), 1) as $r) {
			$country = trim(strip_tags($this->match('/<td>(.*?)<\/td>/ms', $r, 1)));
			$date = trim(strip_tags($this->match('/<td class="release_date">(.*?)<\/td>/ms', $r, 1)));
			array_push($releaseDates, $country . " = " . $date);
		}
		return array_filter($releaseDates);
	}

	// Scan all AKA Titles.
	private function getAkaTitles($html){
		$akaTitles = array();
		foreach($this->match_all('/<tr.*?>(.*?)<\/tr>/msi', $this->match('/<table id="akas".*?>(.*?)<\/table>/ms', $html, 1), 1) as $m) {
			$akaTitleMatch = $this->match_all('/<td>(.*?)<\/td>/ms', $m, 1);
			$akaCountry = trim($akaTitleMatch[0]);
			$akaTitle = trim($akaTitleMatch[1]);
			array_push($akaTitles, $akaTitle . " = " . $akaCountry);
		}
		return array_filter($akaTitles);
	}

	// Collect all Media Images.
	private function getMediaImages($titleId){
		$url  = "https://www.imdb.com/title/" . $titleId . "/mediaindex";
		$html = $this->geturl($url);
		$media = array();
		$media = array_merge($media, $this->scanMediaImages($html));
		foreach($this->match_all('/<a.*?>(\d*)<\/a>/ms', $this->match('/<span class="page_list">(.*?)<\/span>/ms', $html, 1), 1) as $p) {
			$html = $this->geturl($url . "?page=" . $p);
			$media = array_merge($media, $this->scanMediaImages($html));
		}
		return $media;
	}

	// Scan all media images.
	private function scanMediaImages($html){
		$pics = array();
		foreach($this->match_all('/src="(.*?)"/msi', $this->match('/<div class="media_index_thumb_list".*?>(.*?)<\/div>/msi', $html, 1), 1) as $i) {
			array_push($pics, preg_replace('/_V1\..*?.jpg/ms', "_V1._SY0.jpg", $i));
		}
		return array_filter($pics);
	}
	
	// Get recommended titles by IMDb title id.
	public function getRecommendedTitles($titleId){
		$json = $this->geturl("https://www.imdb.com/widget/recommendations/_ajax/get_more_recs?specs=p13nsims%3A${titleId}");
		$resp = json_decode($json, true);
		$arr = array();
		if(isset($resp["recommendations"])) {
			foreach($resp["recommendations"] as $val) {
				$name = $this->match('/title="(.*?)"/msi', $val['content'], 1);
				$arr[$val['tconst']] = $name;
			}
		}
		return array_filter($arr);
	}
	
	// Get all Videos and Trailers
	public function getVideos($titleId){
		$html = $this->geturl("https://www.imdb.com/title/${titleId}/videogallery");
		$videos = array();
		foreach ($this->match_all('/<a.*?href="(\/videoplayer\/vi\d*?)".*?>.*?<\/a>/ms', $html, 1) as $v) {
			$videos[] = "https://www.imdb.com${v}";
		}
		return array_filter($videos);
	}
	
	// Get Top 250 Movie List
	public function getTop250(){
		$html = $this->geturl("https://www.imdb.com/chart/top");
		$top250 = array();
		$rank = 1;
		foreach ($this->match_all('/<tr class="(even|odd)">(.*?)<\/tr>/ms', $html, 2) as $m) {
			$id = $this->match('/<td class="titleColumn">.*?<a href="\/title\/(tt\d+)\/.*?"/msi', $m, 1);
			$title = $this->match('/<td class="titleColumn">.*?<a.*?>(.*?)<\/a>/msi', $m, 1);
			$year = $this->match('/<td class="titleColumn">.*?<span class="secondaryInfo">\((.*?)\)<\/span>/msi', $m, 1);
			$rating = $this->match('/<td class="ratingColumn"><strong.*?>(.*?)<\/strong>/msi', $m, 1);
			$poster = $this->match('/<td class="posterColumn">.*?<img src="(.*?)"/msi', $m, 1);
			$poster = preg_replace('/_V1.*?.jpg/ms', "_V1._SY200.jpg", $poster);
			$url = "https://www.imdb.com/title/${id}/";
			$top250[] = array("id"=>$id, "rank"=>$rank, "title"=>$title, "year"=>$year, "rating"=>$rating, "poster"=>$poster, "url"=>$url);
			$rank++;
		}
		return $top250;
	}

	//************************[ Extra Functions ]******************************

	// Movie title search on Google, Bing or Ask. If search fails, return FALSE.
	private function getIMDbIdFromSearch($title, $engine = "google"){
		switch ($engine) {
			case "google":  $nextEngine = "bing";  break;
			case "bing":    $nextEngine = "ask";   break;
			case "ask":     $nextEngine = FALSE;   break;
			case FALSE:     return NULL;
			default:        return NULL;
		}
		$url = "https://www.${engine}.com/search?q=imdb+" . rawurlencode($title);
		$ids = $this->match_all('/<a.*?href="\/url\?q=https?:\/\/www.imdb.com\/title\/(tt\d+).*?".*?>.*?<\/a>/ms', $this->geturl($url), 1);
		if (!isset($ids[0]) || empty($ids[0])) //if search failed
			return $this->getIMDbIdFromSearch($title, $nextEngine); //move to next search engine
		else
			return $ids[0]; //return first IMDb result
	}
	
	private function geturl($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		$ip=rand(0,255).'.'.rand(0,255).'.'.rand(0,255).'.'.rand(0,255);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("REMOTE_ADDR: $ip", "HTTP_X_FORWARDED_FOR: $ip"));
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/".rand(3,5).".".rand(0,3)." (Windows NT ".rand(3,5).".".rand(0,2)."; rv:2.0.1) Gecko/20100101 Firefox/".rand(3,5).".0.1");
		$html = curl_exec($ch);
		curl_close($ch);
		return $html;
	}

	private function match_all_key_value($regex, $str, $keyIndex = 1, $valueIndex = 2){
		$arr = array();
		preg_match_all($regex, $str, $matches, PREG_SET_ORDER);
		foreach($matches as $m){
			$arr[$m[$keyIndex]] = trim($m[$valueIndex]);
		}
		return $arr;
	}
	
	private function match_all($regex, $str, $i = 0){
		if(preg_match_all($regex, $str, $matches) === false)
			return false;
		else
			return $matches[$i];
	}

	private function match($regex, $str, $i = 0){
		if(preg_match($regex, $str, $match) == 1)
			return $match[$i];
		else
			return false;
	}

	private function endsWith($haystack, $needle)
    {
        $length = strlen($needle);

        return $length === 0 ||
            (substr($haystack, -$length) === $needle);
    }
}
?>
