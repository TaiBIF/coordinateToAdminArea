<?php
class Polygon {

	static $db = false;

	private $verbose = false;
	private $box = null;
	private $grids = array();


	private function o ($meta, $output) {
		if ($this->verbose) {
			echo $meta . "\n";
			var_dump($output);
			echo "\n";
		}
	}


	function __construct ($rid=null, $pid=null) {
		if (is_null($rid)) {
			$this->rid = 1;
		}
		else {
			$this->rid = $rid;
		}
		if (is_null($pid)) {
			$this->pid = 1;
		}
		else {
			$this->pid = $pid;
		}
	}


	static function dbConnect () {
		$host="localhost";
		$user="";
		$passwd="";
		Polygon::$db = new PDO("mysql:host=".$host.";dbname="."GeoGrids", $user, $passwd, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	}


	function loadRegionPolygon ($rid=null, $pid=null, $prefix='') {
		if (!Polygon::$db) {
			Polygon::dbConnect();
		}
		if (is_null($rid)) {
			$rid = $this->rid;
		}
		if (is_null($pid)) {
			$pid = $this->pid;
		}
		$sql = "select * from ".$prefix."region_coordinates where `rid`=$rid and `polygon_id`=$pid order by weight asc;";
		$this->o("Query", $sql);
		$res = Polygon::$db->query($sql);
		$ci = 0;
		$prev = array();
		while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
			if (empty($prev)) {
				$prev = array('x' => $row['longitude'], 'y' => $row['latitude']);
			}

			// 內插法~~~~得修
			if ($row['longitude'] - $prev['x'] >= 0.005) {
				$step = ceil(($row['longitude'] - $prev['x']) / 0.005);
				$yDist = ($row['latitude'] - $prev['y']) / $step;
				for ($i=1; $prev['x'] + $i * 0.005 < $row['longitude']; $i++) {
					$dx = $prev['x'] + $i * 0.005;
					$dy = $prev['y'] + $i * $yDist;
					$this->grids[(string)$this->_floorDec($dx)][(string)$this->_floorDec($dy)]['pog'] = true; // polygon overlap grid
				}
			}
			else if ($row['longitude'] - $prev['x'] <= -0.005) {
				$step = ceil(($row['longitude'] - $prev['x']) / -0.005);
				$yDist = ($row['latitude'] - $prev['y']) / $step;
				for ($i=1; $prev['x'] + $i * -0.005 > $row['longitude']; $i++) {
					$dx = $prev['x'] + $i * -0.005;
					$dy = $prev['y'] + $i * $yDist;
					$this->grids[(string)$this->_floorDec($dx)][(string)$this->_floorDec($dy)]['pog'] = true; // polygon overlap grid
				}
			}

			if ($row['latitude'] - $prev['y'] >= 0.005) {
				$step = ceil(($row['latitude'] - $prev['y']) / 0.005);
				$xDist = ($row['longitude'] - $prev['x']) / $step;
				for ($i=1; $prev['y'] + $i * 0.005 < $row['latitude']; $i++) {
					$dy = $prev['y'] + $i * 0.005;
					$dx = $prev['x'] + $i * $xDist;
					$this->grids[(string)$this->_floorDec($dx)][(string)$this->_floorDec($dy)]['pog'] = true; // polygon overlap grid
				}
			}
			else if ($row['latitude'] - $prev['y'] <= -0.005) {
				$step = ceil(($row['latitude'] - $prev['y']) / -0.005);
				$xDist = ($row['longitude'] - $prev['x']) / $step;
				for ($i=1; $prev['y'] + $i * -0.005 > $row['latitude']; $i++) {
					$dy = $prev['y'] + $i * -0.005;
					$dx = $prev['x'] + $i * $xDist;
					$this->grids[(string)$this->_floorDec($dx)][(string)$this->_floorDec($dy)]['pog'] = true; // polygon overlap grid
				}
			}


			$this->coords[$ci]['x'] = $row['longitude'];
			$this->coords[$ci]['y'] = $row['latitude'];
			$this->grids[(string)$this->_floorDec($row['longitude'])][(string)$this->_floorDec($row['latitude'])]['pog'] = true; // polygon overlap grid
			$ci++;
			$prev = array('x' => $row['longitude'], 'y' => $row['latitude']);
		}
		//$this->o("多邊形坐標", $this->coords);
		//$this->o("最初的格子們", $this->grids);
		return $this->coords;
	}

	function createBox ($useDB = true, $prefix='') {
		if ($useDB) {
			$sql = "select max(longitude) as maxx, min(longitude) as minx, max(latitude) as maxy, min(latitude) as miny from ".$prefix."region_coordinates where `rid`='".$this->rid."' and `polygon_id`='".$this->pid."';";
			$this->o("Query", $sql);
			$res = Polygon::$db->query($sql);
			$row = $res->fetch(PDO::FETCH_ASSOC);
			$this->box['maxx'] = $this->_floorDec($row['maxx']) + 0.01;
			$this->box['minx'] = $this->_floorDec($row['minx']) - 0.01;
			$this->box['maxy'] = $this->_floorDec($row['maxy']) + 0.01;
			$this->box['miny'] = $this->_floorDec($row['miny']) - 0.01;
			$this->o("邊界", $this->box);
			return $this->box;
		}
	}

	function getBox () {
		return $this->box;
	}

	private function _floorDec ($num) {
		$num = floor($num * 1000);
		$diff = $num % 5;
		$num -= $diff;
		return $num / 1000;
	}

	static function floorDec ($num) {
		$num = floor($num * 1000);
		$diff = $num % 5;
		$num -= $diff;
		return $num / 1000;
	}

	static function close () {
		Polygon::$db = null;
	}

	static function getDB () {
		if (!Polygon::$db) {
			Polygon::dbConnect();
		}
		return Polygon::$db;
	}


	function pointInBox($x, $y) {
		if (($x <= $this->box['maxx'])&&($x >= $this->box['minx'])&&($y <= $this->box['maxy'])&&($y >= $this->box['miny'])) {
			if (($this->box['maxx'] - $x < 0.00001)||($x - $this->box['minx'] < 0.00001)||($this->box['maxy'] - $y < 0.00001)||($y - $this->box['miny'] < 0.00001)) {
				return 'edge';
			}
			return 'in';
		}
		return 'out';
	}

	function floodFill ($x=null, $y=null, $setOut=null) {
		$this->o('FF called', array('x' => $x, 'y' => $y));
		static $out = false;
		static $count = 0;
		static $touched = array();

		//if ($count >= 10000) {
		//	return;
		//}

		if (!is_null($setOut)) {
			$out = $setOut;
			$touched = array();
			//$count = 0;
		}

		if (is_null($x)&&is_null($y)) {
			$x = $this->box['maxx'] - 0.005;
			$y = $this->box['maxy'] - 0.005;
		}

		$pob = $this->pointInBox($x, $y);
		$this->o('Point on Box', $pob);

		if ($pob != 'out') {
			if (!is_null(@$this->grids[(string)$x][(string)$y]['pog'])||(@$touched[(string)$x][(string)$y]==true)) {
				$this->o("遇到已標記的格子", array('x' => $x, 'y' => $y));
				return;
			}
			else {
				$touched[(string)$x][(string)$y] = true;
				$this->o("處理此格子", array('x' => $x, 'y' => $y));
				if ($pob == 'edge') {
					$this->o("遇到邊惹", array('x' => $x, 'y' => $y));
					$out = true;
					return;
				}
				else {
					$this->floodFill($x - 0.005, $y, NULL);
					$this->floodFill($x, $y - 0.005, NULL);
					$this->floodFill($x + 0.005, $y, NULL);
					$this->floodFill($x, $y + 0.005, NULL);
				}
			}
		}
		return array($touched, $out);
	}

	private function _fillGrids ($ret) {
		$touched = $ret[0];
		$out = $ret[1];
		if (!empty($touched)) {
			foreach ($touched as $tx => $tykeys) {
				foreach ($tykeys as $ty => $dummy) {
					$this->grids[(string)$tx][(string)$ty]['pog'] = !$out;
				}
			}
		}
	}

	function smartFF () {
		$tmp = $this->grids;
		$count = 0;
		$stop = 0;
		foreach ($tmp as $x => $ykeys) {
			foreach ($ykeys as $y => $in) {
				if ($count == $stop) {
					// $this->verbose = true;
				}
				else {
					$this->verbose = false;
				}
				$this->o('本格為', array('x'=>$x, 'y'=>$y));
				$r1 = $this->floodFill($x - 0.005, $y, false);
				$this->_fillGrids($r1);
				$r2 = $this->floodFill($x, $y - 0.005, false);
				$this->_fillGrids($r2);
				$r3 = $this->floodFill($x + 0.005, $y, false);
				$this->_fillGrids($r3);
				$r4 = $this->floodFill($x, $y + 0.005, false);
				$this->_fillGrids($r4);
				if ($count == $stop) {
					$this->px = $x;
					$this->py = $y;
				}
				$count++;

			}
			if ($count == $stop) {
			}
		}
	}

	function draw ($filename = "polygonGrids.png") {
		$width = round(($this->box['maxx'] - $this->box['minx']) / 0.005 + 1, 0);
		$height = round(($this->box['maxy'] - $this->box['miny']) / 0.005 + 1, 0);
		$cellsize = 5;
		#echo $width . ", " . $height . "\n";
		$im = @imagecreate($width * $cellsize, $height * $cellsize);
		$bgColor = imagecolorallocate($im, 0, 0, 0);
		$gridColor = imagecolorallocate($im, 255, 0, 0);
		$outColor = imagecolorallocate($im, 0, 0, 255);
		$startColor = imagecolorallocate($im, 0, 255, 0);
		foreach ($this->grids as $x => $ykeys) {
			foreach ($ykeys as $y => $in) {
				if ($in['pog']) {
					$dx = round((($this->_floorDec($x) - $this->box['minx']) / 0.005) * $cellsize, 0) ;
					$dy = round((($this->_floorDec($y) - $this->box['miny']) / 0.005) * $cellsize, 0) ;
					$dy = $height * $cellsize - $dy - $cellsize;
					imagefilledrectangle($im, $dx, $dy, $dx + $cellsize, $dy + $cellsize, $gridColor);
					#echo $dx . "\n";
				}
				else {
					$dx = round((($this->_floorDec($x) - $this->box['minx']) / 0.005) * $cellsize, 0) ;
					$dy = round((($this->_floorDec($y) - $this->box['miny']) / 0.005) * $cellsize, 0) ;
					$dy = $height * $cellsize - $dy - $cellsize;
					imagefilledrectangle($im, $dx, $dy, $dx + $cellsize, $dy + $cellsize, $outColor);
					#echo $dx . "\n";
				}
			}
		}
		// 121.435, 24.375
		$dx = round((($this->_floorDec($this->px) - $this->box['minx']) / 0.005) * $cellsize, 0) ;
		$dy = round((($this->_floorDec($this->py) - $this->box['miny']) / 0.005) * $cellsize, 0) ;
		$dy = $height * $cellsize - $dy - $cellsize;
		imagefilledrectangle($im, $dx, $dy, $dx + $cellsize, $dy + $cellsize, $startColor);

		imagepng($im, __DIR__ . '/../images/' . $filename);
		imagedestroy($im);
	}

	function getGrids () {
		return $this->grids;
	}


}

/* use case
$plg = new Polygon(1,1);
$plg->loadRegionPolygon();
$plg->createBox();
$plg->smartFF();
$grids = $plg->getGrids();
var_dump($grids);
$plg->draw();
//*/


?>
