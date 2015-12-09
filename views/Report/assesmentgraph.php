<?php
include("../../public/phpgraphlib-master/phpgraphlib.php");
$graph = new PHPGraphLib(500,400, "generated_graphs/assesmentsgraph.png");
//$data=unserialize(urldecode(stripslashes($_GET['mydata'])));
$data=array(100,12,43,342,9);
$graph->addData($data);
$graph->setTitle("Test Scores");
$graph->setTextColor("black");
$graph->setBarColor("#6da2ff");
$graph->createGraph();
?>
