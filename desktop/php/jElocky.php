<?php
if (! isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

plugin::byId('jElocky');
sendVarToJS('eqType', jElocky::class);
sendVarToJS('DATA_DIR', jElockyUtil::getRelativeDataDir());

jElocky::patch_core();

/* @var array[jElocky_user] $eqUsers */
$eqUsers = eqLogic::byType('jElocky_user');

/* @var array[jElocky_place] $eqPlaces */
$eqPlaces = eqLogic::byType('jElocky_place');

/* @var array[jElocky_object] $eqObjects */
/**
 *
 * @var Ambiguous $eqObjects
 */
$eqObjects = eqLogic::byType('jElocky_object');

function displayActionCard($_action_name, $_action, $_eqLogic_type, $_fa_icon, $_card_color) {
    $eqLogic_type = isset($_eqLogic_type) ? ' data-eqLogic_type="' . $_eqLogic_type . '"' : '';
    echo '<div class="cursor eqLogicAction" data-action="' . $_action . '"' . $eqLogic_type .
        ' style="text-align:center;height:200px;width:160px;margin-bottom:10px;padding:5px;border-radius:2px;margin-right:10px;float:left;" >';
    echo '<div class="center-block" style="width:130px;height:130px;display:flex;align-items: center;justify-content:center;">';
    echo '<i class="fa ' . $_fa_icon . '" style="font-size:6em;color:' . $_card_color . ';"></i>';
    echo "</div>";
    echo '<span style="font-size:1.1em;font-weight:bold;position:relative;top:10px;word-break:break-all;white-space:pre-wrap;word-wrap:break-word;">' .
        $_action_name . '</span>';
    echo '</div>';
}

/**
 *
 * @param eqLogic $_eqLogic
 */
function displayEqLogicCard($_eqLogic) {
    $opacity = $_eqLogic->getIsEnable() ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
    echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $_eqLogic->getId() . '" data-eqLogic_type="' .
        get_class($_eqLogic) .
        '" style="text-align:center;height:200px;width:160px;margin-bottom:10px;padding:5px;border-radius:2px;margin-right:10px;float:left;' .
        $opacity . '" >';
    echo '<div class="center-block" style="width:130px;height:130px;background-color:#f8d800;border-radius:30px;display:flex;align-items:center;">';
    echo '<img src="' . $_eqLogic->getPhoto() .
        '" class="img-responsive center-block" style="max-width:105px;max-height:105px;border-radius:20px;"/>';
    echo "</div>";
    echo '<span style="font-size:1.1em;position:relative;top:10px;word-break:break-all;white-space:pre-wrap;word-wrap:break-word;">' .
        $_eqLogic->getHumanName(true, true) . '</span>';
    echo '</div>';
}

?>

<div id="div_newEqptMsg"></div>
<div class="row row-overflow">
    <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="bs-sidebar">
            <a class="btn btn-default eqLogicAction" style="width: 100%; margin-top: 5px; margin-bottom: 5px;"
                data-action="add" data-eqLogic_type="jElocky_user"><i class="fa fa-plus-circle"></i> {{Ajouter un
                utilisateur}}</a>
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <li><i class="fa fa-male"></i><span> {{Utilisateurs}}</span>
                    <ul class="nav nav-list bs-sidenav sub-nav-list">
                <?php
                foreach ($eqUsers as $eqL) {
                    $opacity = ($eqL->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
                    echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqL->getId() .
                        '" data-eqLogic_type="jElocky_user" style="' . $opacity . '"><a>' . $eqL->getHumanName(true) .
                        '</a></li>';
                }
                ?>
                </ul></li>
                <li><i class="fa fa-home"></i><span> {{Lieux}}</span>
                    <ul class="nav nav-list bs-sidenav sub-nav-list">
                <?php
                foreach ($eqPlaces as $eqL) {
                    $opacity = ($eqL->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
                    echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqL->getId() .
                        '" data-eqLogic_type="jElocky_place" style="' . $opacity . '"><a>' . $eqL->getHumanName(true) .
                        '</a></li>';
                }
                ?>
                </ul></li>
                <li><i class="fa fa-key"></i><span> {{Objets}}</span>
                    <ul class="nav nav-list bs-sidenav sub-nav-list">
                <?php
                foreach ($eqObjects as $eqL) {
                    $opacity = ($eqL->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
                    echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqL->getId() .
                        '" data-eqLogic_type="jElocky_object" style="' . $opacity . '"><a>' . $eqL->getHumanName(true) .
                        '</a></li>';
                }
                ?>
                </ul></li>
            </ul>
        </div>
    </div>

    <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay"
        style="border-left: solid 1px #EEE; padding-left: 25px;">
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-6">
                <form><fieldset>
                    <legend><i class="fa fa-male"></i> {{Utilisateurs}}</legend>
                    <?php
                    foreach ($eqUsers as $eqU) {
                        displayEqLogicCard($eqU);
                    }
                    displayActionCard('{{Ajouter un utilisateur}}', 'add', 'jElocky_user', 'fa-plus-circle', '#f8d800');
                    ?>
                </fieldset></form>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <form><fieldset>
                    <legend><i class="fa fa-cog"></i> {{Gestion}}</legend>
                    <?php
                    displayActionCard('{{Configuration}}', 'gotoPluginConf', null, 'fa-wrench', '#767676');
                    ?>
                </fieldset></form>
            </div>
        </div>

        <?php
        if (count($eqPlaces) > 0) {
            echo '<form><fieldset><legend><i class="fa fa-home"></i> {{Lieux}}</legend>';
            echo '<ul class="nav nav-tabs" role="tablist">';
            $active = ' class="active"';
            foreach ($eqPlaces as $eqP) {
                echo '<li role="presentation"' . $active . '><a href="#' . $eqP->getId() .
                    '" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-home"></i> ' . $eqP->getName() .
                    '</a></li>';
                $active = '';
            }
            echo '</ul>';

            echo '<div class="tab-content"><br>';
            $active = ' in active';
            /* @var jElocky_place $eqP */
            foreach ($eqPlaces as $eqP) {
                echo '<div id="' . $eqP->getId() . '" class="tab-pane fade' . $active . '">';
                displayEqLogicCard($eqP);
                echo '<div class="clearfix"></div>';
                $active = '';
                $eqObjects = $eqP->getObjects();
                if (count($eqObjects) > 0) {
                    echo '<form><fieldset><legend><i class="fa fa-key"></i>  {{Objets}}</legend>';
                    foreach ($eqObjects as $eqO) {
                        displayEqLogicCard($eqO);
                    }
                    echo '</fieldset></form>';
                }
                echo '</div>';
            }
            echo '</div>';
            echo '</fieldset></form>';
        }
        ?>
    </div>

    <?php include_file('desktop', 'jElocky_user', 'php', 'jElocky'); ?>
    <?php include_file('desktop', 'jElocky_place', 'php', 'jElocky'); ?>
    <?php include_file('desktop', 'jElocky_object', 'php', 'jElocky'); ?>
</div>

<?php include_file('desktop', 'jElocky', 'js', 'jElocky');?>
<?php include_file('core', 'plugin.template', 'js');?>
