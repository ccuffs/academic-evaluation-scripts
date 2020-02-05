
# This script test different machine learning models to
# predict the emotional state of users when playing
#
# Lots of ideas from: http://machinelearningmastery.com/machine-learning-in-r-step-by-step/

library(gridExtra);
library(grid);
library(optparse);
library(digest);
library(ggplot2);
library(magrittr);
library(plyr);
library(scales);
library(fs);
library(stringr);
library(data.table);

# Set default theme for ggplot2 charts
theme_set(theme_bw());

#####################################################################
# The main program starts here
######################################################################

option_list = list(
    make_option(c("--dataset"), type="character", default="../../../data/2019/from-json.csv", help="Path to the CSV file to be used as a dataset. [default: %default]", metavar="<string>"),
    make_option(c("--type"), type="character", default="individual", help="Type of report to be created. Available options are individual and group. [default: %default]", metavar="<string>"),
    make_option(c("--filter"), type="character", default="Fernando", help="TODO. [default: %default]", metavar="<string>"),    
    make_option(c("--output-dir"), type="character", default="../../../results/2019/", help="Directory where result files, e.g. plots, will be outputed. [default: %default]", metavar="<string>")
);

opt_parser = OptionParser(option_list=option_list);
opt = parse_args(opt_parser);

# Make command line params global
CONFIG <- opt;

# Print warnings as they occur
options(warn=1);

# Include all definitions and functions
source("common-functions.r");

# Things to be used
dataset_path = opt$"dataset";
output_dir = opt$"output-dir";
type = opt$"type";
filter = opt$"filter";

# Load data
data = load.data(dataset_path);
forms_data = filter.forms.using.title(data, filter);
form_ids = unique(forms_data$form_id);

cat(sprintf("Processing forms (%d in total)\n", length(form_ids)));

for(form_id in form_ids) {
    form_data = filter.data(data, "form_id", form_id);

    meta = extract.metadata(form_data[1, "form_title"]);
    available_questions = unique(form_data$question_number);
    respondents = unique(form_data$respondent);

    cat(sprintf("- %s (respondents: %d)\n   %s (%s %s)\n   %s\n", form_id, length(respondents), meta["course_name"], meta["course_period"], meta["course_modality"], meta["course_responsible"]));

    for(question_number in available_questions) {
        # Create a folder to house the plots
        form_dir_path = sprintf("%s/%s", output_dir, form_id);
        dir.create(form_dir_path, showWarnings = FALSE, recursive = TRUE);
        report_file_path = sprintf("%s/%d.pdf", form_dir_path, question_number);

        # Get the data
        question_data = filter.data(form_data, "question_number", question_number);

        if(question_number == 18) {
            # Text related to suggestions, we can't plot.
            report_file_path = sprintf("%s/%d.csv", form_dir_path, question_number);
            write.csv(question_data, file=report_file_path, row.names = FALSE);
            next;
        }

        #response_order = c("excelente", "bom","regular", "ruim", "péssimo", "péssima");
        #x_data = factor(question_data$response, levels=response_order);
        x_data = factor(question_data$response);

        p = ggplot(question_data, aes(x = x_data)) +
                geom_bar(aes(y = (..count..)/sum(..count..))) +
                geom_text(aes(y = ((..count..)/sum(..count..)), label = ..count..), stat = "count", vjust = -0.25) +
                scale_y_continuous(labels = percent) +
                labs(y = "Percentagem", x = "Respostas")
        
        suppressMessages(ggsave(report_file_path, p));
    }
}

cat(sprintf("All done!\n"));
