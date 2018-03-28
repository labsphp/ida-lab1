<?php

/**
 * Created by PhpStorm.
 * User: Serhii
 * Date: 21.03.2018
 * Time: 0:04
 */

/*
 * https://www.power-eng.com/index/more-power-generation-news.html - articles about energy
 * https://www.cnbc.com/finance/ - articles about finance
 * https://www.homesandproperty.co.uk/property-news - articles about properties
 */
declare(strict_types = 1);
include_once "BayesClassification.php";

$trainingDataSet = include("loadTrainingData.php");

$bayesClassification = new BayesClassification("class");
$bayesClassification->teach($trainingDataSet);

$dataSet = include("loadDataSet.php");
$classes = $bayesClassification->classify($dataSet);
echo '<pre>';
print_r($classes);
echo '<pre>';


