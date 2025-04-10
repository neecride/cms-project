            <div class="container">
                <?php
                $activeWidget = $GetParams->GetParam(4, 'param_activ');
                if($activeWidget == "oui"): 
                    require_once RACINE.DS.'public'.DS.'templates'.DS.$GetParams->themeForLayout().DS.'parts'.DS.'widgets'.DS.'top'.DS.'widgetAlert.php'; 
                endif; 
                ?>
                <!-- Header End -->
                <?= $session->flash(); ?>
                <?= $contentForLayout; ?>
            </div>
