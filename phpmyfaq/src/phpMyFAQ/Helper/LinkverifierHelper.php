<?php

namespace phpMyFAQ\Helper;

/**
 * Linkverifier Helper class for phpMyFAQ.
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2012-09-03
 */

use phpMyFAQ\Helper;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Helper_Linkverifier.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2012-09-03
 */
class LinkverifierHelper extends Helper
{
    /**
     * Prints JavaScript needed for AJAX verification on record add/save/clicked on listing.
     *
     * @param int    $id
     * @param string $lang
     */
    public static function linkOndemandJavascript($id, $lang)
    {
        ?>
    <script type="text/javascript">
        function ajaxOnDemandVerify(id, lang)
        {
            var target = $('#onDemandVerifyResult');
            var url = 'index.php';
            var pars = 'action=ajax&ajax=onDemandURL&id=' + id + '&artlang=' + lang + '&lookup=1';
            var myAjax = new jQuery.ajax({url: url,
                type: 'get',
                data: pars,
                complete: ajaxOnDemandVerify_success,
                error: ajaxOnDemandVerify_failure});
            //TODO: Assign string
            target.innerHTML = 'Querying LinkVerifier...';

            function ajaxOnDemandVerify_success(XmlRequest)
            {
                target.html(XmlRequest.responseText);
            }

            function ajaxOnDemandVerify_failure(XmlRequest)
            {
                //TODO: Assign string
                target.html('LinkVerifier failed (url probe timed out?)');
            }
        }

        ajaxOnDemandVerify(<?= $id ?>, '<?= $lang ?>');

    </script>
    <?php
    }
}
