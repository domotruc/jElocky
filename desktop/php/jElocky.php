<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

$plugin = plugin::byId('jElocky');
sendVarToJS('eqType', jElocky::class);
sendVarToJS('DATA_DIR', jElockyUtil::getRelativeDataDir());
$eqUsers = eqLogic::byType('jElocky_user');
$eqPlaces = eqLogic::byType('jElocky_place');

function displayActionCard($_action_name, $_action, $_eqLogic_type, $_fa_icon, $_card_color) {
    $eqLogic_type = isset($_eqLogic_type) ? ' data-eqLogic_type="' . $_eqLogic_type . '"' : '';
    echo '<div class="cursor eqLogicAction" data-action="' . $_action . '"' . $eqLogic_type . ' style="text-align:center;background-color:#ffffff;height:150px;margin-bottom:10px;padding:5px;border-radius:2px;width:160px;margin-left:10px;" >';
    echo '<i class="fa ' . $_fa_icon . '" style="font-size:6em;color:' . $_card_color . ';"></i>';
    echo '<br>';
    echo '<span style="font-size:1.1em;font-weight:bold;position:relative;top:23px;word-break:break-all;white-space:pre-wrap;word-wrap:break-word;color:' . $_card_color . ';">' . $_action_name . '</span>';
    echo '</div>';
}

function displayEqLogicCard($_eqLogic) {
    $opacity = $_eqLogic->getIsEnable() ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
    echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $_eqLogic->getId() . '" data-eqLogic_type="' . get_class($_eqLogic) . '" style="text-align:center;background-color:#ffffff;height:200px;margin-bottom:10px;padding:5px;border-radius:2px;width:160px;margin-left:10px;' . $opacity . '" >';
    echo '<div class="center-block" style="width:130px;height:130px;background-color:#f8d800;border-radius:30px;display:flex;align-items:center;">';
    echo '<img src="' . $_eqLogic->getPhoto() . '" class="img-responsive center-block" style="max-width:105px;max-height:105px;border-radius:20px;"/>';
    //echo '<i class="fa fa-male" style="font-size:6em;color:#f8d800;"></i>';
    //echo '<img src="' . $plugin->getPathImgIcon() . '" height="105" width="95" />';
    echo "</div>";
    echo '<span style="font-size:1.1em;position:relative;top:10px;word-break:break-all;white-space:pre-wrap;word-wrap: break-word;">' . $_eqLogic->getHumanName(true, true) . '</span>';
    echo '</div>';
}

//include __DIR__  . '/../../core/class/jElocky_user.class.php';

//jElocky_user::test();

//print_r(get_declared_classes());

/*$cls = array('jElocky', 'jElockyCmd', 'jElocky_user', 'jElocky_userCmd');
   foreach($cls as $cl) {
   echo $cl . ':' . class_exists($cl) . '<br>';
   }*/
?>

<div class="row row-overflow">
    <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="bs-sidebar">
            <a class="btn btn-default eqLogicAction" style="width:100%;margin-top:5px;margin-bottom:5px;" data-action="add" data-eqLogic_type="jElocky_user"><i class="fa fa-plus-circle"></i> {{Ajouter un utilisateur}}</a>
            <a class="filter" style="margin-bottom:5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></a>
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <li><i class="fa fa-male"></i><span>  {{Utilisateurs}}</span><ul class="nav nav-list bs-sidenav sub-nav-list">
                <?php
                /*foreach ( $eqLogics as $eqLogic ) {
                 $opacity = ($eqLogic->getIsEnable ()) ? '' : jeedom::getConfiguration ( 'eqLogic:style:noactive' );
                 echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId () . '" data-eqLogic_type="jElocky" style="' . $opacity . '"><a>' . $eqLogic->getHumanName ( true ) . '</a></li>';
                 }*/
                foreach ($eqUsers as $eqUser) {
                    $opacity = ($eqUser->getIsEnable ()) ? '' : jeedom::getConfiguration ( 'eqLogic:style:noactive' );
                    echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqUser->getId () . '" data-eqLogic_type="jElocky_user" style="' . $opacity . '"><a>' . $eqUser->getHumanName ( true ) . '</a></li>';
                }
                ?>
                </ul></li>
                <li><i class="fa fa-home"></i><span>  {{Lieux}}</span><ul class="nav nav-list bs-sidenav sub-nav-list">
                <?php
                foreach ($eqPlaces as $eqPlace) {
                    $opacity = ($eqPlace->getIsEnable ()) ? '' : jeedom::getConfiguration ( 'eqLogic:style:noactive' );
                    echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqPlace->getId () . '" data-eqLogic_type="jElocky_place" style="' . $opacity . '"><a>' . $eqPlace->getHumanName ( true ) . '</a></li>';
                }                
                ?>
                </ul></li>
            </ul>
        </div>
    </div>

    <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left:solid 1px #EEE;padding-left:25px;">
        <legend><i class="fa fa-cog"></i>  {{Gestion}}</legend>
        <div class="eqLogicThumbnailContainer">
            <?php
            displayActionCard('{{Ajouter un utilisateur}}', 'add', 'jElocky_user', 'fa-plus-circle', '#f8d800');
            displayActionCard('{{Configuration}}', 'gotoPluginConf', null, 'fa-wrench', '#767676');
            ?>
        </div>
        <legend><i class="fa fa-male"></i>  {{Utilisateurs}}</legend>
        <ul class="nav nav-tabs" role="tablist">
            <?php
            $active = ' class="active"';
            foreach ($eqUsers as $eqUser) {
                echo '<li role="presentation"' . $active  . '><a href="#' . $eqUser->getId() . '" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-male"></i> ' . $eqUser->getName() . '</a></li>';
                $active = '';
            }
            ?>
        </ul>

        <div class="tab-content">
            <?php
            $active = ' in active';
            foreach ($eqUsers as $eqUser) {
                echo '<div id="' . $eqUser->getId() . '" class="tab-pane fade' . $active . '">';
                displayEqLogicCard($eqUser);
                $active = '';
                       	       
                $eqPlaces = $eqUser->getPlaces();
                if (count($eqPlaces) > 0)
                    echo '<legend><i class="fa fa-home"></i>  {{Lieux}}</legend>';
        	       foreach ($eqPlaces as $eqPlace) {
        	           displayEqLogicCard($eqPlace);
        	       }
        	       echo '</div>';
            }
            ?>
        </div>

        <!-- 
        <div class="cursor eqLogicAction" data-action="add" data-eqLogic_type="jElocky_user" style="text-align:center;background-color:#ffffff;height:150px;margin-bottom:10px;padding:5px;border-radius:2px;width:160px;margin-left:10px;" >
            <i class="fa fa-plus-circle" style="font-size:6em;color:#f8d800;"></i>
            <br>
            <span style="font-size:1.1em;font-weight:bold;position:relative;top:23px;word-break:break-all;white-space:pre-wrap;word-wrap:break-word;color:#f8d800;">{{Ajouter}}</span>
        </div>
        -->

        <!-- 
        <div class="eqLogicThumbnailContainer">
            <legend>  jElocky</legend>
            <?php /*
            foreach ($eqLogics as $eqLogic) {
	        $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
	        echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" data-eqLogic_type="jElocky" style="text-align: center; background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
	        echo '<img src="' . $plugin->getPathImgIcon() . '" height="105" width="95" />';
	        echo "<br>";
	        echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;">' . $eqLogic->getHumanName(true, true) . '</span>';
	        echo '</div>';
            } */
            ?>
        </div>
        <div class="eqLogicThumbnailContainer">
            <legend>  jElocky_user</legend>
            <?php /*
            foreach ($eqLogicUsers as $eqLogic) {
	        $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
	        echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" data-eqLogic_type="jElocky_user" style="text-align: center; background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
	        echo '<img src="' . $plugin->getPathImgIcon() . '" height="105" width="95" />';
	        echo "<br>";
	        echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;">' . $eqLogic->getHumanName(true, true) . '</span>';
	        echo '</div>';
            }*/
            ?>
        </div>
        -->
    </div>

    <?php include_file('desktop', 'jElocky_user', 'php', 'jElocky'); ?>
    <?php include_file('desktop', 'jElocky_place', 'php', 'jElocky'); ?>
</div>

<?php include_file('desktop', 'jElocky', 'js', 'jElocky');?>
<?php include_file('core', 'plugin.template', 'js');?>
