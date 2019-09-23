<?php
$park = null;
if (isset($_GET['park'])) 
{
    $park = $_GET['park'];
    $data = file_get_contents('https://russellvalefootball.com/test.json');
    $characters = json_decode($data); 
    foreach ($characters as $item) 
    {
       if ($item->park_name == $park)
       { 
        echo json_encode($item);
        break;
       }
    }
    echo "nothing found"
}
else
{
    echo "You need to specify a park in the URL using the format ?park={park name}.";
}
?>
