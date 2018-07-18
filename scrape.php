<?php
/**
 * Created by PhpStorm.
 * User: emodatt08
 * Date: 17/01/2017
 * Time: 9:43 AM
 */
//include("class.form/process.php");

include("crawler/simple_html_dom.php");


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <title>News</title>


    <link href="http://getbootstrap.com/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="http://getbootstrap.com/examples/jumbotron-narrow/jumbotron-narrow.css" rel="stylesheet">
    <link href="../static/signup.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet">
    <script type = "text/javascript" src = "../static/jquery-3.1.1.min.js"></script>

    <style type="text/css">

        #p{

            font-size:16px;
        }
    </style>

</head>

<body>

<div class="container">
    <div class="header">
        <nav>
            <ul class="nav nav-pills pull-right">
                <li role="presentation" ><a href="">Home</a></li>
                <li role="presentation" class="active"><a href="#">Trending</a></li>
            </ul>
        </nav>
        <h3 class="text-muted">Flash</h3>

        </div>
<!--    <div class="jumbotron" >-->
<!--        <form method = "POST"  action="#">-->
<!--        <div class="form-group">-->
<!---->
<!--            <input class="form-control input-lg" id="inputlg" type="text" name = "query">-->
<!--        </div>-->
<!--        <input type="submit" class="btn btn-info" name = "search" style="height:60px; width:200px;" value ="Search"/>-->
<!--        </form>-->
<!--    </div>-->


    <div class="jumbotron">
        <?php


//        $url2 = "https://en.oxforddictionaries.com/definition/" . $query;    // Assigning the URL we want to scrape to the variable $url
//
//        $html = file_get_html($url2);
//        echo '<span style="font-size: large; color:darkblue;" >' . $html->find('span[class=hw]')[0] . '</span>' . "<br/>";
//        echo "<br/>";
//        echo $html->find('span[class=ind]')[0];
//        echo "<br/>";
//        echo "<a target=\"_blank\" href='" . $url2 . "'>https://en.oxforddictionaries.com/definition/$query</a>";
        ?>
    </div>

    <div class="jumbotron" id="p">

<!--        -->
        <?php
//        $titles = [];
//        $descriptions = [];
//        $urls = [];
//
//        $url = "steve%20Ballmer";
//        $term = str_replace(" ", "_", $query);


//        $url="http://en.wikipedia.org/w/api.php?action=query&prop=extracts|info&exintro&titles=$term&format=json&explaintext&redirects&inprop=url&indexpageids";
//
//        $json = file_get_contents($url);
//        $data = json_decode($json, TRUE);
//
//        foreach ($data['query']['pages'] as $page) {
//            $titles[] = $page['title'];
//            $descriptions[] = $page['extract'];
//            $urls[] = $page['fullurl'];
//        }
//        foreach($titles as $title)
//         echo $title."<br/>";
//        foreach($descriptions as $description)
//        echo mb_strimwidth($description, 0, 89, "...")."<br/>";
//        foreach($urls as  $url)
//        echo $url;

        // Find all images
//        $html = file_get_html('https://en.wikipedia.org/wiki/'.$term);
//        foreach($html->find('a') as $element)
//            //echo $element->href . '<br>';
//
//        echo $element->href. '<br>';
//
//        ?>
    </div>

    <div class="jumbotron">

        <?php

                $url2 = "http://ghanamotion.com";    // Assigning the URL we want to scrape to the variable $url

                $html = file_get_html($url2);

                $titles = [];
                $titlesUrl = [];
                $date = [];
                $description = [];
                $images = [];
                $otherLinks = [];
                $mp3Links = [];


                foreach ($html->find('h2[class=post-box-title]') as $element):
                    $titles[] = $element->plaintext . '<br/>';
                endforeach;

                foreach ($html->find('.entry p') as $element):
                    $description[] = $element->plaintext . '<br/>';
                endforeach;

                foreach ($html->find('h2[class=post-box-title] a') as $element):
                    $titlesUrl[] = $element . '<br/>';
                endforeach;

                foreach ($html->find('span[class=tie-date]') as $element):
                    $date[] = $element . '<br/>';
                endforeach;

                foreach ($html->find('div[class=post-thumbnail]') as $element):
                    $images[] = $element. '<br/>';
                endforeach;

//        for ($i = 0; $i < count($titles); $i++):
//            $href = str_get_html($titles[$i]);
//            echo getDownloadLinks($href).'<br/>';
//                   //echo $titles[$i].'<br/>';
//                endfor;


                for ($i = 0; $i < count($titlesUrl); $i++):
                    $href = str_get_html($titlesUrl[$i]);
                    $mp3 = file_get_html(getDownloadLinks($href));

                    echo 'Title:'.$titles[$i].'<br/> Description:'
                      . $date[$i].'<br/> Image: '. $images[$i].'<br/> Description: '
                    . $description[$i].'<br/>';
                    foreach($mp3->find('a[class=zbPlayer-download]') as $mp3Links){
                        echo 'Mp3Link: '.$mp3Links->href.'<hr/>';
                    }
                endfor;
    function getDownloadLinks($link){
        //print_r($link);die();
        try{
                preg_match_all('/<a[^>]+href=([\'"])(?<href>.+?)\1[^>]*>/i', $link, $result);
                if (!empty($result)) {
                    # Found a link.
                    return $result['href'][0];
                }
        }catch(\Exception $e){
            return $e->getMessage();
        }

    }


        ?>
    </div>


    <footer class="footer">
        <p>&copy; Company 2015</p>
    </footer>

</div>
</body>
</html>