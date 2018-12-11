<div class="col-lg-10 col-md-9 col-sm-8 eqLogic jElocky_place" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
    <a class="btn btn-success eqLogicAction pull-right" data-action="save" data-eqLogic_type="jElocky_place"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
    <a class="btn btn-danger eqLogicAction pull-right" data-action="remove" data-eqLogic_type="jElocky_place"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
    <a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>
    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation"><a class="eqLogicAction cursor" aria-controls="home" role="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
        <li role="presentation" class="active"><a href="#place_eqtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Equipement}}</a></li>
        <li role="presentation"><a href="#place_cmdtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
    </ul>
    <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
        <div role="tabpanel" class="tab-pane active" id="place_eqtab">
            <br/>
            <form class="form-horizontal">
                <fieldset>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{Nom}}</label>
                        <div class="col-sm-3">
                            <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display:none;" />
                            <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom}}"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label" >{{Objet parent}}</label>
                        <div class="col-sm-3">
                            <select class="eqLogicAttr form-control" data-l1key="object_id">
                                <option value="">{{Aucun}}</option>
                                <?php
                                foreach (object::all() as $object) {
	                            echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
	                   <div class="form-group">
                        <label class="col-sm-3 control-label">{{Catégorie}}</label>
                        <div class="col-sm-9">
                            <?php
                            foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                                echo '<label class="checkbox-inline">';
                                $checked = $key == 'security' ? "checked" : "";
                                echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '"' . $checked . '/>' . $value['name'];
                                echo '</label>';
                            }
                            ?>
                        </div>
                    </div>
	                   <div class="form-group">
		                      <label class="col-sm-3 control-label"></label>
		                      <div class="col-sm-9">
			                         <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
			                         <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
		                      </div>
	                   </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{Adresse}}</label>
                        <div class="col-sm-3">
                            <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="address" placeholder="address" readonly/>
                        </div>
                    </div> 
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{Complément}}</label>
                        <div class="col-sm-3">
                            <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="complement" placeholder="complement" readonly/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{Code postal}}</label>
                        <div class="col-sm-3">
                            <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="zip_code" placeholder="zip_code" readonly/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{Ville}}</label>
                        <div class="col-sm-3">
                            <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="city" placeholder="city" readonly/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{Pays}}</label>
                        <div class="col-sm-3">
                            <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="country" data-l3key="name" placeholder="country" readonly/>
                        </div>
                    </div>
                    <div class="form-group" style="display:none;">
                        <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="photo" placeholder="photo" readonly/>
                    </div>
                    <div class="form-group col-sm-6">
    												        <img id="photo_place" name="photo" src="core/img/no_image.gif" class="img-responsive center-block" style="max-height:250px;border-radius:20px;"/>
			                 </div>
                </fieldset>
            </form>
        </div>
        <div role="tabpanel" class="tab-pane" id="place_cmdtab">
            <a class="btn btn-success btn-sm cmdAction pull-right" data-action="add" style="margin-top:5px;"><i class="fa fa-plus-circle"></i> {{Commandes}}</a><br/><br/>
            <table
                id="place_table_cmd"
                class="table table-bordered table-condensed">
                <thead>
                    <tr>
                        <th style="width: 250px;">{{Nom}}</th>
                        <th style="width: 60px;">{{Type}}</th>
                        <th style="width: 300px;">{{Valeur}}</th>
                        <th style="width: 60px;">{{Unité}}</th>
                        <th style="width: 150px;">{{Paramètres}}</th>
                        <th style="width: 150px;">{{Actions}}</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>
