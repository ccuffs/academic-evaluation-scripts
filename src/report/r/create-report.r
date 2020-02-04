
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

theme_set(theme_bw());

#####################################################################
# The main program starts here
######################################################################

option_list = list(
    make_option(c("--dataset"), type="character", default="../../../data/2019/from-json.csv", help="Path to the CSV file to be used as a dataset. [default: %default]", metavar="<string>"),
    make_option(c("--type"), type="character", default="individual", help="Type of report to be created. Available options are individual and group. [default: %default]", metavar="<string>"),
    make_option(c("--subject"), type="character", default="", help="TODO. [default: %default]", metavar="<string>"),    
    make_option(c("--subject-column"), type="character", default="form_id", help="TODO. [default: %default]", metavar="<string>"),
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
subject = opt$"subject";
subject_column = opt$"subject-column";

# Load data
data = load.data(dataset_path);
form_ids = unique(data$form_id);

cat(sprintf("Processing forms (%d in total)\n", length(form_ids)));

for(form_id in form_ids) {
    form_data = filter.data(data, "form_id", form_id);
    available_questions = unique(form_data$question_number);
    respondents = unique(form_data$respondent);

    cat(sprintf("  %s (respondents=%d)\n", form_id, length(respondents)));

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

        p = ggplot(question_data, aes(x = as.factor(response))) +
                geom_bar(aes(y = (..count..)/sum(..count..))) +
                geom_text(aes(y = ((..count..)/sum(..count..)), label = ..count..), stat = "count", vjust = -0.25) +
                scale_y_continuous(labels = percent) +
                labs(y = "Percentagem", x = "Respostas")
        
        suppressMessages(ggsave(report_file_path, p));
    }
}

cat(sprintf("All done!\n"));
