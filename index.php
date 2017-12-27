<?php
require_once("imdb.php");


if(isset($_GET["moviename"]) | isset($_GET["movieid"])) {
	$movieName = $_GET["moviename"];
	$movieId = $_GET["movieid"];
    $movieUrl = $_GET["movieurl"];
	$output = strtolower($_GET["output"]);

	$i = new Imdb();
	$mArr = [];
	if($movieId !== '') {
		$mArr = array_change_key_case($i->getMovieInfoById($movieId), CASE_UPPER);
	} else if($movieName !== '') {
		$mArr = array_change_key_case($i->getMovieInfo($movieName), CASE_UPPER);
    } else if($movieUrl !== '') {
        $mArr = array_change_key_case($i->getMovieInfoByURL($movieUrl), CASE_UPPER);
	} else {
		echo 'Please give either a moviename or movieid';
	}

	///////////////[ XML Output ]/////////////////
	if($output == "xml") {
		header("Content-Type: text/xml");
		$doc = new DomDocument('1.0');
		$doc->formatOutput = true;
		$movie = $doc->createElement('MOVIE');
		$movie = $doc->appendChild($movie);
		foreach ($mArr as $k=>$v){
			if(is_array($v)){
				$node = $doc->createElement($k);
				$node = $movie->appendChild($node);
				$c = 0;
				foreach($v as $a){
					$c++;
					$child = $doc->createElement($k . "_");
					$child = $node->appendChild($child);
					$child->setAttribute('n', $c);
					$value = $doc->createTextNode($a);
					$value = $child->appendChild($value);
				}
			} else {
				$node = $doc->createElement($k);
				$node = $movie->appendChild($node);
				$value = $doc->createTextNode($v);
				$value = $node->appendChild($value);
			}
		}
		$xml_string = $doc->saveXML();
		echo $xml_string;
	} else if($output == "json") {
		header('Content-type: application/json');
		echo json_encode($mArr);
	} else {
		echo 'Please choose an output format (xml/json)';
	}
	//Output html
} else {
	?>
	<!DOCTYPE html>
	<html>
	<head>
	<title>PHP-IMDb-Scraper</title>
	<style>
		html, body {
			width:100%;
			height:100%;
			margin: 0;
			padding:0;
			font-family: Arial, serif;
			font-size:13px;
			background: -moz-linear-gradient(top,  rgba(188,188,188,1) 0%, rgba(255,255,255,1) 100%); /* FF3.6-15 */
			background: -webkit-linear-gradient(top,  rgba(188,188,188,1) 0%,rgba(255,255,255,1) 100%); /* Chrome10-25,Safari5.1-6 */
			background: linear-gradient(to bottom,  rgba(188,188,188,1) 0%,rgba(255,255,255,1) 100%); /* W3C, IE10+, FF16+, Chrome26+, Opera12+, Safari7+ */
			filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#bcbcbc', endColorstr='#ffffff',GradientType=0 ); /* IE6-9 */
		}
		#imdbform {
			width:500px;
			margin:auto;
			display:flex;
			height:100%;
			align-items:center;
			justify-content:center;
		}
		.formline {
			padding:5px;
		}
		.formline>label {
			display:inline-block;
			width: 150px;
			color: #989898;
		}
		.formline>input {
			border: 1px solid #ccc;
			width:200px;
			font-size: inherit;
			padding: 5px;
		}
		
		.formline>select {
			width:210px;
			border: 1px solid #cccccc;
		    background-color: #fff;
		    height:30px;
		}
		.formline>button {
			border: 1px solid #cccccc;
		    width: 100%;
		    height: 30px;
		    background-color: #fff;
		    color: #000;
		}
		.formline>input:hover, .formline>button:hover,.formline>select:hover {
			border: 1px solid #aaa;
		}
	</style>
	</head>

	<body>
		<div id="imdbform">
			<form action="" method="get">
				<div class="formline">
					<label for="movieid">Either IMDB ID:</label>
					<input type="text" placeholder="i.e. tt0848228" name="movieid" id="movieid"/>
				</div>
				<div class="formline">
					<label for="moviename">Or Movie Name:</label>
					<input type="text" placeholder="i.e. The Avengers" name="moviename" id="moviename"/>
				</div>
                <div class="formline">
                    <label for="movieurl">Or Movie URL:</label>
                    <input type="text" placeholder="i.e. http://www.imdb.com/title/tt0848228/" name="movieurl" id="movieurl"/>
                </div>
				<div class="formline">
					<label for="output">Output format:</label>
					<select id="output" name="output">
						<option value="json">JSON</option>
						<option value="xml">XML</option>
					</select>
				</div>
				<div class="formline">
					<button type="submit" id="submit">Scrape!</button>
				</div>
			</form>
		</div>
	</body>

	</html>
	<?php
}
?>
