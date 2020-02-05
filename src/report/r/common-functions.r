library(data.table);
library(dplyr);
library(sjmisc);

#
# This file contains several functions that are used by more
# than one script.
#

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

plot.form.data <- function(form_data, output_dir, label) {
    available_questions = unique(form_data$question_number);

    for(question_number in available_questions) {
        # Create a folder to house the plots
        dir.create(output_dir, showWarnings = FALSE, recursive = TRUE);
        report_file_path = sprintf("%s/%d%s.pdf", output_dir, question_number, label);

        # Get the data
        question_data = filter.data(form_data, "question_number", question_number);

        if(question_number == 18) {
            # Text related to suggestions, we can't plot.
            report_file_path = sprintf("%s/%d%s.csv", output_dir, question_number, label);
            write.csv(question_data, file=report_file_path, row.names = FALSE);
            next;
        }

        # Aggregate things
        question_data_by_modality = question_data %>% group_by(modality);
        aggregated_question_data_by_modality = question_data_by_modality %>% group_by(response) %>% summarise(
            amount = n()
        );

        x_data = factor(question_data$response);

        p = ggplot(question_data, aes(x = x_data)) +
                geom_bar(aes(y = (..count..)/sum(..count..))) +
                geom_text(aes(y = ((..count..)/sum(..count..)), label = ..count..), stat = "count", vjust = -0.25) +
                scale_y_continuous(labels = percent) +
                labs(y = "Percentagem", x = "Resposta")
        
        suppressMessages(ggsave(report_file_path, p));
    }
}
