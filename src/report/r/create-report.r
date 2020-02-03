
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
library(dplyr);

#####################################################################
# The main program starts here
######################################################################

option_list = list(
    make_option(c("--dataset"), type="character", default="../../../data/2019.csv", help="Path to the CSV file to be used as a dataset. [default: %default]", metavar="<string>"),
    make_option(c("--type"), type="character", default="individual", help="Type of report to be created. Available options are individual and group. [default: %default]", metavar="<string>"),
    make_option(c("--subject"), type="character", default="", help="TODO. [default: %default]", metavar="<string>"),    
    make_option(c("--subject-column"), type="character", default="form_title", help="TODO. [default: %default]", metavar="<string>"),
    make_option(c("--output-dir"), type="character", default="../../../results/", help="Directory where result files, e.g. plots, will be outputed. [default: %default]", metavar="<string>")
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

cat(sprintf("dataset_path: %s\noutput_dir: %s\n", dataset_path, output_dir));

data = load.data(dataset_path);
forms = unique(data$form_title);

for(form in forms) {
    form_data = filter.data(data, subject_column, form);
    available_questions = unique(form_data$question_title);

    for(question in available_questions) {
        question_data = filter.data(form_data, "question_title", question);

        ggplot(question_data, aes(x=Accuracy)) +
            geom_histogram(alpha=0.5, position="identity")
        ggsave(sprintf("%s/%s-hist.pdf", output_dir, "test"));

    }
}
