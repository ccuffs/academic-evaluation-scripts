library(data.table);
library(dplyr);
library(sjmisc);
library(tm);
library(SnowballC);
library(wordcloud);
library(RColorBrewer);

#
# This file contains several functions that are used by more
# than one script.
#

wrapper <- function(x, ...) {
  paste(strwrap(x, ...), collapse = "\n")
}

load.data <- function(file_path) {
    if (!file.exists(file_path)) {
    	stop(sprintf("[ERROR] File %s not found! \n", file_path), call.=FALSE);
    }

    # Load the subject's CSV
    data = read.csv(file_path, header=TRUE, sep=",", dec=".", as.is=TRUE, stringsAsFactors=FALSE, encoding = "UTF-8");
    return(data);
}

adjust.modality.form.data <- function(data, manifest_data) {
    data$modality = "";

    for(id in unique(data$form_id)) {
        meta = manifest_data[manifest_data$form_id == id,];
        data[data$form_id == id, "modality"] = meta["course_modality"];
    }
    
    return(data);
}

# Filter a dataframe based on the value of a particular column.
filter.data <- function(data, column_name, column_value) {
    return(data[data[,column_name] == column_value,]);
}

filter.forms.using.title <- function(data, title_value) {
    if(filter == "") {
        # No filter
        return(data);
    } else {
        # Some filter is in place
        filtered_data = data %>% dplyr::filter(form_title %ilike% title_value);
        return(filtered_data);
    }
}

plot.form.data.text.minining <- function(output_dir, question_data, question_title, question_number, label) {
    # First of all write the text to a file
    report_file_path = sprintf("%s/%d%s.csv", output_dir, question_number, label);
    write.csv(question_data, file=report_file_path, row.names = FALSE);

    # Text mining: http://www.sthda.com/english/wiki/text-mining-and-word-cloud-fundamentals-in-r-5-simple-steps-you-should-know

    text = question_data$response;
    
    # Load the data as a corpus
    docs = Corpus(VectorSource(text));

    toSpace <- content_transformer(function (x , pattern ) gsub(pattern, " ", x));
    
    docs = tm_map(docs, toSpace, "/");
    docs = tm_map(docs, toSpace, "@");
    docs = tm_map(docs, toSpace, "\\|");

    # Convert the text to lower case
    docs = tm_map(docs, content_transformer(tolower));
    # Remove numbers
    docs = tm_map(docs, removeNumbers);
    # Remove english common stopwords
    docs = tm_map(docs, removeWords, stopwords("portuguese"));
    # Remove your own stop word
    # specify your stopwords as a character vector
    docs = tm_map(docs, removeWords, c("professor", "professora", "aluno", "aluna", "alunos", "alunas", "aula", "aulas"));
    # Remove punctuations
    docs = tm_map(docs, removePunctuation);
    # Eliminate extra white spaces
    docs = tm_map(docs, stripWhitespace);
    # Text stemming
    # docs <- tm_map(docs, stemDocument)

    # Build a term-document matrix
    dtm = TermDocumentMatrix(docs);
    m = as.matrix(dtm);
    v = sort(rowSums(m), decreasing=TRUE);
    d = data.frame(word = names(v), freq=v);

    # Generate the Word cloud
    wordcloud_file_path = sprintf("%s/%d%s.pdf", output_dir, question_number, label);
    pdf(wordcloud_file_path);

    set.seed(1234)
    wordcloud(words = d$word, freq = d$freq, min.freq = 1,
              max.words=200, random.order=FALSE, rot.per=0.35, 
              colors=brewer.pal(8, "Dark2"));
    
    dev.off();

    # Explore frequent terms and their associations
    # findFreqTerms(dtm, lowfreq = 4);

    # Plot word frequencies
    plot_file_path = sprintf("%s/%d%s-a.pdf", output_dir, question_number, label);

    most_frequent_words = d[1:10,];
    p = ggplot(most_frequent_words, aes(x = word, y = freq)) +
            geom_bar(stat="identity") + 
            labs(y = "Frequencia das palavras", x = "Palavras")
    suppressMessages(ggsave(plot_file_path, p));

}

plot.form.data <- function(form_data, output_dir, label="") {
    available_questions = unique(form_data$question_number);

    for(question_number in available_questions) {
        # Create a folder to house the plots
        dir.create(output_dir, showWarnings = FALSE, recursive = TRUE);
        report_file_path = sprintf("%s/%d%s.pdf", output_dir, question_number, label);

        # Get the data
        question_data = filter.data(form_data, "question_number", question_number);

        if(question_number == 18) {
            # Text related to suggestions, we can't plot.
            plot.form.data.text.minining(output_dir, question_data, question_title, question_number, label);
            next;
        }

        # Aggregate things
        question_data_by_modality = question_data %>% group_by(modality);
        aggregated_question_data_by_modality = question_data_by_modality %>% group_by(response) %>% summarise(
            amount = n()
        );

        x_data = factor(question_data$response);
        question_title = question_data[1, "question_title"];

        p = ggplot(question_data, aes(x = x_data)) +
                geom_bar(aes(y = (..count..)/sum(..count..))) +
                geom_text(aes(y = ((..count..)/sum(..count..)), label = ..count..), stat = "count", vjust = -0.25) +
                ggtitle(wrapper(question_title, width = 70)) +
                scale_y_continuous(labels = percent) +
                labs(y = "Percentagem", x = "Resposta")
        
        suppressMessages(ggsave(report_file_path, p));
    }
}
