function runSendResponseStats() {
  var aStats = findAnsweringStats();
  var aMinResponses = 2;
  var aTextResponses = "";
  var aTextFewerResponses = "";
  var aAttachments = [];
  
  for(var i = 0; i < aStats.length; i++) {
    var s = aStats[i];
    
    if(s.responses >= aMinResponses) {
      aTextResponses += s.title + "\n" + "Responses: " + s.responses + "\n" + "Summary: " + s.summaryUrl + "\n\n";
    } else {
      aTextFewerResponses += s.title + "\n" + "Responses: " + s.responses + "\n" + "Summary: " + s.summaryUrl + "\n\n";
      
      // Create a file with a message directed to the students of a particular course
      // regarding the evaluation questionnaire. This file will be attached to an e-mail.
      var aBlob = createDirectStudentInvitationMailAttachment(s.form);
      aAttachments.push(aBlob);
    }
  }

  var aTextMail = MAIL_RESPONSE_STATS_TEXT
    .replace("{@listResponses}", aTextResponses)
    .replace("{@listFewerResponses}", aTextFewerResponses);
  
  MailApp.sendEmail(MAIL_TO, MAIL_SUBJECT, aTextMail + MAIL_TEXT_FOOTER, {name: "Bot Coordenação CC", cc: "fernando.bevilacqua@uffs.edu.br", attachments: aAttachments});
}
