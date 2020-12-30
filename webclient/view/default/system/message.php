<?php
/**
 * Bibliotheca webclient
 *
 * Copyright 2018-2020 Johannes Keßler
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

if(isset($TemplateData['message']['content'])) {
    $cssClass = 'primary';
    if(isset($TemplateData['message']['status'])) {
        switch($TemplateData['message']['status']) {
            case 'error':
                $cssClass = 'danger';
            break;
            case 'warning':
                $cssClass = 'warning';
            break;
            case 'success':
                $cssClass = 'success';
            break;

            case 'info':
            default:

        }
    }
?>
<div class="uk-alert-<?php echo $cssClass; ?>" uk-alert>
    <p><?php echo $TemplateData['message']['content']; ?></p>
</div>
<?php } ?>
