function createDirectStudentInvitationMailAttachment(theForm, theRemoveQuestionnairePrefix) {
  if(theForm == null) {
    return null;
  }
  
  // By default, remove any prefix in the title to create attached files with shorter names.
  theRemoveQuestionnairePrefix = theRemoveQuestionnairePrefix == undefined ? true : false;
  
  var aTitle = theForm.getTitle();
  var aShortenURL = theForm.shortenFormUrl(theForm.getPublishedUrl());
  
  if(true || theRemoveQuestionnairePrefix) {
    aTitle = aTitle.replace(QUESTIONNAIRE_TITLE_PREFIX, "").trim();
  }

  var aTextMail = MAIL_DIRECT_STUDENT_INVITATION
    .replace("{@courseName}", aTitle)
    .replace("{@courseEvaluationLink}", aShortenURL);
  
  var aBlob = Utilities.newBlob(aTextMail, "text/plain", aTitle + ".txt");
  return aBlob;
}
