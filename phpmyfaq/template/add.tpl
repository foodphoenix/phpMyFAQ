<h2>{msgNewContentHeader}</h2>
	<p>{msgNewContentAddon}</p>
	<form action="{writeSendAdress}" method="post" style="display: inline">
	<fieldset>
    <legend>{msgNewContentHeader}</legend>
    
    <label for="username">{msgNewContentName}</label>
    <input class="inputfield" type="text" name="username" id="username" size="50" /><br />
	
    <label for="usermail">{msgNewContentMail}</label>
    <input class="inputfield" type="text" name="usermail" id="usermail" size="50" /><br />
	
    <label for="rubrik">{msgNewContentCategory}</label>
    <select name="rubrik" id="rubrik">
    {printCategoryOptions}
    </select><br />
	
    <label for="thema">{msgNewContentTheme}</label>
    <textarea class="inputarea" cols="50" rows="3" name="thema" id="thema">{printQuestion}</textarea><br />
	
    <label for="content">{msgNewContentArticle}</label>
    <textarea class="inputarea" cols="50" rows="10" name="content" id="content"></textarea><br />
	
    <label for="keywords">{msgNewContentKeywords}</label>
    <input class="inputfield" type="text" name="keywords" id="keywords" size="50" /><br />
    
    <label for="contentlink">{msgNewContentLink}</label>
    <input class="inputfield" type="text" name="contentlink" id="contentlink" size="50" value="http://" /><br />
	
    <input class="submit" type="submit" name="submit" value="{msgNewContentSubmit}" />
	
    </fieldset>
    </form>
    <p>{copyright_eintrag}</p>
