<?php defined('_JEXEC') or die('Restricted access'); ?>
<?php
function extract_year($data)
{
    if (!isset($data['publicationDate_s'])) {
        return null;
    }
    $date = $data["publicationDate_s"];
    return substr($date, 0, 4); //will hold till year 10000, fine by me
}

$data_per_year = array();
foreach ($data["docs"] as $doc) {
    $year = extract_year($doc);
    if (!array_key_exists($year, $data_per_year)) {
        $data_per_year[$year] = array();
    }
    array_push($data_per_year[$year], $doc);
}
reset($data_per_year);
$last_year = key($data_per_year);
?>


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css" integrity="sha512-1PKOgIY59xJ8Co8+NE6FZ+LOAZKjy+KY8iq0G4B3CyeY6wYHN3yt9PW0XpSriVlkMXe40PTKnXrLnZ9+fkDaog==" crossorigin="anonymous" />
<div class="hal-publication">

    <input class="hal-year-input" type="checkbox" id="hal-<?php echo $last_year ?>" name="hal-<?php echo $last_year ?>" checked>
    <label class="hal-year-label fa" for="hal-<?php echo $last_year ?>"><?php echo $last_year ?></label>
    <?php foreach (array_keys(array_slice($data_per_year, 1, null, true)) as $year) { ?>
        <input class="hal-year-input" type="checkbox" id="hal-<?php echo $year ?>" name="hal-<?php echo $year ?>">
        <label class="hal-year-label fa" for="hal-<?php echo $year ?>"><?php echo $year ?></label>
    <?php } ?>

    <?php foreach ($data_per_year as $year => $data) { ?>
        <div class="hal-year" id="hal-data-<?php echo $year ?>">
            <?php foreach ($data as $i => $value) { ?>
                <div class="hal-item<?php echo ($i % 2) ? ' hal-even' : ' hal-odd' ?><?php if ($i === 0) echo ' hal-first' ?>">
                    <?php echo $helper->prepareArticles($value) ?>
                    <div class="hal-clr"></div>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>

<style>
    <?php foreach (array_keys($data_per_year) as $year) {
        echo "#hal-data-$year{
        display: none;
    }
    #hal-$year:checked ~ #hal-data-$year{
        display: initial;
    }
    






    ";


     } ?>
</style>
