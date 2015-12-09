<?php
include("../../public/phpgraphlib-master/phpgraphlib.php");
$graph = new PHPGraphLib(500,400, "topicsgraph.png");
$data=unserialize(urldecode(stripslashes($_GET['mytopic'])));
$graph->addData($data);
$graph->setTitle("Topics Scores");
$graph->setTextColor("black");
$graph->setBarColor("green");
//$graph->setGradient("green", "yellow");
$graph->createGraph();
?>
