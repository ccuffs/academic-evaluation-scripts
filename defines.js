var BASE_FORM_ID = "1Y9DRoAjuA5MdEA06i9Ze7EepSNlO7yaXwReJs0AhQFo";
var EVALUATION_PREFIX = "[2019/01]";
var QUESTIONNAIRE_TITLE_PREFIX = EVALUATION_PREFIX + " Avaliação Componente Curricular:";
var FOLDER_NAME = "Avaliações " + EVALUATION_PREFIX;
var MAIL_TO = "dovyski@gmail.com";
var MAIL_SUBJECT = EVALUATION_PREFIX + " Links Avaliação Componente Curricular";
var MAIL_TEXT = "Olá,\nSeguem os links abaixo.\n\n";
var MAIL_RESPONSE_STATS_TEXT = "Olá,\nSeguem as estatísticas de respostas às avaliações.\n\nAvaliações com várias respostas:\n------------------------------------------------------------------------------------\n\n{@listResponses}\nAvaliações com poucas respostas:\n------------------------------------------------------------------------------------\n\n{@listFewerResponses}\n";
var MAIL_TEXT_FOOTER = "Com carinho,\nBot Coordenação CC\n";
var MAIL_DIRECT_STUDENT_INVITATION = "Oi academica(o),\n\nA Coordenação de Ciência da Computação informa que estamos no período de avaliação das disciplinas. Abaixo está o link para você avaliar a disciplina \"{@courseName}\":\n\n{@courseEvaluationLink}\n\nSuas respostas são muito importantes para a coordenação do curso. Dê suas respostas com tranquilidade, seriedade, e a certeza que elas são totalmente anônimas.\n\nDica: aproveite o campo de texto no final do questionário para escrever criticas, elogios e sugestões sobre a disciplina/professor.\n\nAtenciosamente,\nCoordenação de Ciência da Computação\nUFFS - Chapecó - SC\nhttp://cc.uffs.edu.br";

var aNames = [
  {course: "GEX107 - Computação gráficaTTTTTTTT - 7ª e 10ª Fase - Vespertino e Noturno", name: "FERNANDO BEVILACQUA"}
];