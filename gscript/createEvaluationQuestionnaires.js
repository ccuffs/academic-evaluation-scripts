// TODO: receive BASE_FORM_ID and FOLDER_NAME as params
function createEvaluationQuestionnaires(theData) {
  var aLinksList = "";
  var aBaseForm = DriveApp.getFileById(BASE_FORM_ID);
  var aFolder = DriveApp.createFolder(FOLDER_NAME);
    
  for(var i = 0; i < theData.length; i++) {
    var aCurrentName = theData[i].course + " ("+theData[i].name+")";
    var aCurrentTitle = QUESTIONNAIRE_TITLE_PREFIX + " " + aCurrentName;

    var aNewFile = aBaseForm.makeCopy(aCurrentTitle, aFolder);  
    var aForm = FormApp.openById(aNewFile.getId());

    // Logger.log(aNewFile);
    // Logger.log(aForm);
    // Loger.log(aForm.getPublishedUrl());
      
    aForm.setTitle(aCurrentTitle);
    
    var aCurrentListDesc = aCurrentName + " - " + aForm.shortenFormUrl(aForm.getPublishedUrl()) + "\n\n";
    aLinksList += aCurrentListDesc;

    Logger.log(aCurrentListDesc);
  }
  
  MailApp.sendEmail(MAIL_TO, MAIL_SUBJECT, MAIL_TEXT + aLinksList);
}