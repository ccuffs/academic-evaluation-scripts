library(data.table);

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
        filtered_data = data %>% dplyr::filter(form_title %like% title_value);
        return(filtered_data);
    }
}

extract.metadata <- function(form_title) {
    regex = "\\[(.*)\\].*: ([A-Z]{3}[0-9]{3}) - (.*) - ([0-9].*) (Noturno|Matutino|Vespertino) \\((.*)\\)";
    matches = str_match(form_title, regex);

    meta = c(
        "season" = matches[1, 2],
        "course_id" = matches[1, 3],
        "course_name" = matches[1, 4],
        "course_period" = matches[1, 5],
        "course_modality" = matches[1, 6],
        "course_responsible" = matches[1, 7]
    );

    return(meta);
}
