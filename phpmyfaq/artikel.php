<?php
/**
 * Shows the page with the FAQ record and - when available - the user
 * comments
 *
 * @category  phpMyFAQ
 * @package   Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Lars Tiedemann <larstiedemann@yahoo.de>
 * @since     2002-08-27
 * @version   git: $Id$
 * @copyright 2002-2009 phpMyFAQ Team
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$captcha   = new PMF_Captcha($sids);
$oGlossary = new PMF_Glossary();
$oLnk      = new PMF_Linkverifier();
$tagging   = new PMF_Tags();
$relevant  = new PMF_Relation();
$faqrating = new PMF_Rating();
$comment   = new PMF_Comment();

if (!is_null($showCaptcha)) {
    $captcha->showCaptchaImg();
    exit;
}

$currentCategory = $cat;
$record_id       = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$solution_id     = PMF_Filter::filterInput(INPUT_GET, 'solution_id', FILTER_VALIDATE_INT);
$highlight       = PMF_Filter::filterInput(INPUT_GET, 'highlight', FILTER_SANITIZE_STRIPPED);

$faqsession->userTracking('article_view', $record_id);

// Get all data from the FAQ record
if (0 == $solution_id) {
    $faq->getRecord($record_id);
} else {
    $faq->getRecordBySolutionId($solution_id);
}

$faqvisits = PMF_Visits::getInstance();
$faqvisits->logViews($faq->faqRecord['id']);

$content = $faq->faqRecord['content'];
$thema   = $faq->getRecordTitle($faq->faqRecord['id']);
// Add Glossary entries
$content = $oGlossary->insertItemsIntoContent($content);
$thema   = $oGlossary->insertItemsIntoContent($thema);

// Set the path of the current category
$categoryName = $category->getPath($currentCategory, ' &raquo; ', true);

$changeLanguagePath = PMF_Link::getSystemRelativeUri().sprintf('?%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s', $sids, $currentCategory, $id, $LANGCODE);
$oLink              = new PMF_Link($changeLanguagePath);
$oLink->itemTitle   = $faq->getRecordTitle($record_id, false);
$changeLanguagePath = $oLink->toString();


if (!is_null($highlight) && strlen($highlight) > 3) {
    $highlight   = str_replace("'", "�", $highlight);
    $highlight   = str_replace(array('^', '.', '?', '*', '+', '{', '}', '(', ')', '[', ']'), '', $highlight);
    $highlight   = preg_quote($highlight, '/');
    $searchItems = explode(' ', $highlight);
    $attributes  = array(
        'href', 'src', 'title', 'alt', 'class', 'style', 'id', 'name', 'face',
        'size', 'dir', 'onclick', 'ondblclick', 'onmousedown', 'onmouseup',
        'onmouseover', 'onmousemove', 'onmouseout', 'onkeypress', 'onkeydown',
        'onkeyup');

    foreach ($searchItems as $item) {
        $thema = preg_replace_callback(
            '/'
            // a. the glossary item could be an attribute name
            .'('.$item.'="[^"]*")|'
            // b. the glossary item could be inside an attribute value
            .'(('.implode('|', $attributes).')="[^"]*'.$item.'[^"]*")|'
            // c. the glossary item could be everywhere as a distinct word
            .'(\s+)('.$item.')(\s+)'
            .'/mis',
            'highlight_no_links',
            $thema);
        $content = preg_replace_callback(
            '/'
            // a. the glossary item could be an attribute name
            .'('.$item.'="[^"]*")|'
            // b. the glossary item could be inside an attribute value
            .'(('.implode('|', $attributes).')="[^"]*'.$item.'[^"]*")|'
            // c. the glossary item could be everywhere as a distinct word
            .'(\s+)('.$item.')(\s+)'
            .'/mis',
            'highlight_no_links',
            $content);
    }
}

// Hack: Apply the new SEO schema to those HTML anchors to
//       other faq records (Internal Links) added with WYSIWYG Editor:
//         href="index.php?action=artikel&cat=NNN&id=MMM&artlang=XYZ"
// Search for href attribute links
$oLnk->resetPool();
$oLnk->parse_string($content);
$fixedContent = $content;

// Search for href attributes only
if (isset($oLnk->urlpool['href'])) {
    $linkArray = array_unique($oLnk->urlpool['href']);
    foreach ($linkArray as $_url) {
        if (!(strpos($_url, 'index.php?action=artikel') === false)) {
            // Get the Faq link title
            preg_match('/id=([\d]+)/ism', $_url, $matches);
            $_id   = $matches[1];
            preg_match('/artlang=([a-z\-]+)$/ism', $_url, $matches);
            $_lang = $matches[1];
            $_title = $faq->getRecordTitle($_id, false);
            $_link = substr($_url, 9);
            // Move the link to XHTML
            if (strpos($_url, '&amp;') === false) {
                $_link = str_replace('&', '&amp;', $_link);
            }
            $oLink            = new PMF_Link(PMF_Link::getSystemRelativeUri().$_link);
            $oLink->itemTitle = $_title;
            $oLink->tooltip   = PMF_htmlentities($_title, ENT_QUOTES, $PMF_LANG['metaCharset']);
            $newFaqPath       = $oLink->toString();
            $fixedContent     = str_replace($_url, $newFaqPath, $fixedContent);
        }
    }
}

$content = str_replace('href="#', 'href="index.php?action=artikel&lang='.$LANGCODE.'&id='.$record_id.'#', $content);

// Check for the languages for a faq
$arrLanguage    = PMF_Utils::languageAvailable($record_id);
$switchLanguage = '';
$check4Lang     = '';
$num            = count($arrLanguage);
if ($num > 1) {
    foreach ($arrLanguage as $language) {
        $check4Lang .= "<option value=\"".$language."\"";
        $check4Lang .= ($lang == $language ? ' selected="selected"' : '');
        $check4Lang .= ">".$languageCodes[strtoupper($language)]."</option>\n";
    }
    $switchLanguage .= "<p>\n";
    $switchLanguage .= "<fieldset>\n";
    $switchLanguage .= "<legend>".$PMF_LANG["msgLangaugeSubmit"]."</legend>\n";
    $switchLanguage .= "<form action=\"".$changeLanguagePath."\" method=\"post\" style=\"display: inline;\">\n";
    $switchLanguage .= "<select name=\"language\" size=\"1\">\n";
    $switchLanguage .= $check4Lang;
    $switchLanguage .= "</select>\n";
    $switchLanguage .= "&nbsp;\n";
    $switchLanguage .= "<input class=\"submit\" type=\"submit\" name=\"submit\" value=\"".$PMF_LANG["msgLangaugeSubmit"]."\" />\n";
    $switchLanguage .= "</fieldset>\n";
    $switchLanguage .= "</form>\n";
    $switchLanguage .= "</p>\n";
}

// List all faq attachments
if (isAttachmentDirOk($record_id) && $faqconfig->get('main.disableAttachments') && 'yes' == $faq->faqRecord['active']) {
    $files  = 0;
    $outstr = "";
    $dir    = opendir(PMF_ATTACHMENTS_DIR . DIRECTORY_SEPARATOR .  $record_id);
    while ($dat = readdir($dir)) {
        if ($dat != '.' && $dat != '..') {
            $files++;
            $outstr .= sprintf('<a href="%s" target="_blank">%s</a>, ',
                buildAttachmentUrl($record_id, rawurlencode($dat), true),
                $dat);
        }
    }
    if ($files > 0) {
        $content .= '<p>'.$PMF_LANG['msgAttachedFiles'].' '.substr($outstr, 0, -2).'</p>';
    }
}

// List all categories for this faq
$writeMultiCategories = '';
$multiCategories = $category->getCategoriesFromArticle($record_id);
if (count($multiCategories) > 1) {
    $writeMultiCategories .= '        <div id="article_categories">';
    $writeMultiCategories .= '        <fieldset>';
    $writeMultiCategories .= '                <legend>'.$PMF_LANG['msgArticleCategories'].'</legend>';
    $writeMultiCategories .= '            <ul>';
    foreach ($multiCategories as $multiCat) {
        $writeMultiCategories .= sprintf("<li>%s</li>\n", $category->getPath($multiCat['id'], ' &raquo; ', true));
    }
    $writeMultiCategories .= '            </ul>';
    $writeMultiCategories .= '        </fieldset>';
    $writeMultiCategories .= '    </div>';
}

// Show link to edit the faq?
$editThisEntry = '';
if (isset($permission['editbt'])) {
    $editThisEntry = sprintf(
        '<a href="%sadmin/index.php?action=editentry&amp;id=%d&amp;lang=%s">%s</a>',
        PMF_Link::getSystemRelativeUri('index.php'),
        $record_id,
        $lang,
        $PMF_LANG['ad_entry_edit_1'].' '.$PMF_LANG['ad_entry_edit_2']);
}

// Is the faq expired?
$expired = (date('YmdHis') > $faq->faqRecord['dateEnd']);

// Does the user have the right to add a comment?
if (($faq->faqRecord['active'] != 'yes') || ('n' == $faq->faqRecord['comment']) || $expired) {
    $commentMessage = $PMF_LANG['msgWriteNoComment'];
} else {
    $commentMessage = sprintf(
        "%s<a href=\"javascript:void(0);\" onclick=\"javascript:$('#comment').show();\">%s</a>",
        $PMF_LANG['msgYouCan'],
        $PMF_LANG['msgWriteComment']);
}

// Build Digg it! URL
$diggItUrl = sprintf('%s?cat=%s&amp;id=%d&amp;lang=%s&amp;title=%s',
    PMF_Link::getSystemUri(),
    $currentCategory,
    $record_id,
    $lang,
    urlencode($thema));

// Create commented out HTML for microsummary
$allVisitsData  = $faqvisits->getAllData();
$faqPopularity  = '';
$maxVisits      = 0;
$minVisits      = 0;
$currVisits     = 0;
$faqVisitsCount = count($allVisitsData);
$percentage     = 0;
if ($faqVisitsCount > 0) {
    $percentage = 100/$faqVisitsCount;
}
foreach ($allVisitsData as $_r) {
    if ($minVisits > $_r['visits']) {
        $minVisits = $_r['visits'];
    }
    if ($maxVisits < $_r['visits']) {
        $maxVisits = $_r['visits'];
    }
    if (($record_id == $_r['id']) && ($lang == $_r['lang'])) {
        $currVisits = $_r['visits'];
    }
}
if ($maxVisits - $minVisits > 0) {
    $percentage = 100*($currVisits - $minVisits)/($maxVisits - $minVisits);
}
$faqPopularity = $currVisits.'/'.(int)$percentage.'%';

$translationForm = '';
if (count($arrLanguage) < count(getAvailableLanguages())) {
    $translationUrl = sprintf(str_replace('%', '%%', PMF_Link::getSystemRelativeUri('index.php')).'index.php?%saction=translate&amp;cat=%s&amp;id=%d&amp;srclang=%s', $sids, $currentCategory, $record_id, $lang);
    $translationForm = '
        <form action="'.$translationUrl.'" method="post" style="display: inline;">
            <img src="images/translate.gif" alt="'.$PMF_LANG['msgTranslate'].'" title="'.$PMF_LANG['msgTranslate'].'" width="16" height="16" border="0" /> '.$PMF_LANG['msgTranslate'].' '.selectLanguages($LANGCODE, false, $arrLanguage, 'translation').' <input class="submit" type="submit" name="submit" value="'.$PMF_LANG['msgTranslateSubmit'].'" />
        </form>';
}

// Set the template variables
$tpl->processTemplate ("writeContent", array(
    'writeRubrik'                   => $categoryName.'<br />',
    'solution_id'                   => $faq->faqRecord['solution_id'],
    'writeThema'                    => $thema,
    'writeArticleCategoryHeader'    => $PMF_LANG['msgArticleCategories'],
    'writeArticleCategories'        => $writeMultiCategories,
    'writeContent'                  => preg_replace_callback("/<code([^>]*)>(.*?)<\/code>/is", 'hilight', $content),
    'writeTagHeader'                => $PMF_LANG['msg_tags'] . ': ',
    'writeArticleTags'              => $tagging->getAllLinkTagsById($record_id),
    'writeRelatedArticlesHeader'    => $PMF_LANG['msg_related_articles'] . ': ',
    'writeRelatedArticles'          => $relevant->getAllRelatedById($record_id, $faq->faqRecord['title'], $faq->faqRecord['keywords']),
    'writePopularity'               => $faqPopularity,
    'writeDateMsg'                  => $PMF_LANG['msgLastUpdateArticle'].$faq->faqRecord['date'],
    'writeRevision'                 => $PMF_LANG['ad_entry_revision'].': 1.'.$faq->faqRecord['revision_id'],
    'writeAuthor'                   => $PMF_LANG['msgAuthor'] . ': ' . $faq->faqRecord['author'],
    'editThisEntry'                 => $editThisEntry,
    'writeDiggMsgTag'               => 'Digg it!',
    'link_digg'                     => sprintf('http://digg.com/submit?phase=2&amp;url=%s', urlencode($diggItUrl)),
    'link_email'                    => sprintf(str_replace('%', '%%', PMF_Link::getSystemRelativeUri('index.php')).'index.php?%saction=send2friend&amp;cat=%d&amp;id=%d&amp;artlang=%s', $sids, $currentCategory, $record_id, $lang),
    'link_pdf'                      => sprintf(str_replace('%', '%%', PMF_Link::getSystemRelativeUri('index.php')).'pdf.php?cat=%d&amp;id=%d&amp;artlang=%s', $currentCategory, $record_id, $lang),
    'writePDFTag'                   => $PMF_LANG['msgPDF'],
    'writePrintMsgTag'              => $PMF_LANG['msgPrintArticle'],
    'writeSend2FriendMsgTag'        => $PMF_LANG['msgSend2Friend'],
    'translationForm'               => $translationForm,
    'saveVotingPATH'                => sprintf(str_replace('%', '%%', PMF_Link::getSystemRelativeUri('index.php')).'index.php?%saction=savevoting', $sids),
    'saveVotingID'                  => $record_id,
    'saveVotingIP'                  => $_SERVER['REMOTE_ADDR'],
    'msgAverageVote'                => $PMF_LANG['msgAverageVote'],
    'printVotings'                  => $faqrating->getVotingResult($record_id),
    'switchLanguage'                => $switchLanguage,
    'msgVoteUseability'             => $PMF_LANG['msgVoteUseability'],
    'msgVoteBad'                    => $PMF_LANG['msgVoteBad'],
    'msgVoteGood'                   => $PMF_LANG['msgVoteGood'],
    'msgVoteSubmit'                 => $PMF_LANG['msgVoteSubmit'],
    'writeCommentMsg'               => $commentMessage,
    'msgWriteComment'               => $PMF_LANG['msgWriteComment'],
    'writeSendAdress'               => '?'.$sids.'action=savecomment',
    'id'                            => $record_id,
    'lang'                          => $lang,
    'msgCommentHeader'              => $PMF_LANG['msgCommentHeader'],
    'msgNewContentName'             => $PMF_LANG['msgNewContentName'],
    'msgNewContentMail'             => $PMF_LANG['msgNewContentMail'],
    'defaultContentMail'            => ($user instanceof PMF_User_CurrentUser) ? $user->getUserData('email') : '',
    'defaultContentName'            => ($user instanceof PMF_User_CurrentUser) ? $user->getUserData('display_name') : '',
    'msgYourComment'                => $PMF_LANG['msgYourComment'],
    'msgNewContentSubmit'           => $PMF_LANG['msgNewContentSubmit'],
    'captchaFieldset'               => printCaptchaFieldset($PMF_LANG['msgCaptcha'], $captcha->printCaptcha('writecomment'), $captcha->caplength),
    'writeComments'                 => $comment->getComments($record_id, PMF_Comment::COMMENT_TYPE_FAQ)));

$tpl->includeTemplate('writeContent', 'index');
