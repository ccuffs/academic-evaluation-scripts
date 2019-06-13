/** 
 *
 */
function findAnsweringStats() {
  var aRoot = DriveApp.getFoldersByName(FOLDER_NAME);
  var aRet = [];
  
  while(aRoot.hasNext()) {
    var aFolder = aRoot.next();
    var aFiles = aFolder.getFiles();

    while(aFiles.hasNext()) {
      var aFile = aFiles.next();
      
      if(aFile.getMimeType() != "application/vnd.google-apps.form") {
        continue;
      }
      
      var aForm = FormApp.openById(aFile.getId());
      var aResponses = aForm.getResponses();
      
      aRet.push({responses: aResponses.length, title: aForm.getTitle(), summaryUrl: aForm.getSummaryUrl(), form: aForm});
      //Logger.log("Responses: " + aResponses.length + ", title: " + aForm.getTitle() + ", summary: " + aForm.getSummaryUrl());
    }
  }
  
  return aRet;
}
