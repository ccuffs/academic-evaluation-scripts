
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
library(waffle);

# Set default theme for ggplot2 charts
theme_set(theme_bw());

#####################################################################
# The main program starts here
######################################################################

option_list = list(
    make_option(c("--dataset"), type="character", default="../../../data/2019/from-json.csv", help="Path to the CSV file to be used as a dataset. [default: %default]", metavar="<string>"),
    make_option(c("--dataset-manifest"), type="character", default="../../../data/2019/from-json.csv.manifest.csv", help="Path to the CSV file to be used as the manifest for the loaded dataset. [default: %default]", metavar="<string>"),
    make_option(c("--dataset-questions"), type="character", default="../../../data/2019/from-json.csv.questions.csv", help="Path to the CSV file containing the questions present in the dataset. [default: %default]", metavar="<string>"),
    make_option(c("--text-questions"), type="character", default="18", help="List of strings separated by comma representing the number of the questions that are text-based. [default: %default]", metavar="<string>"),
    make_option(c("--filter"), type="character", default="", help="TODO. [default: %default]", metavar="<string>"),    
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
dataset_manifest_path = opt$"dataset-manifest";
dataset_questions_path = opt$"dataset-questions";
text_mode_questions = strsplit(opt$"text-questions", ",")[[1]];
output_dir = opt$"output-dir";
filter = opt$"filter";

# Load raw data
data = load.data(dataset_path);
manifest_data = load.data(dataset_manifest_path);
questions_data = load.data(dataset_questions_path);

data = adjust.modality.form.data(data, manifest_data);

# Select data based on filter
forms_data = filter.forms.using.title(data, filter);
form_ids = unique(forms_data$form_id);

cat(sprintf("Text questions:\n"));
print(text_mode_questions);

cat(sprintf("Individual reports (%d forms in total)\n", length(form_ids)));

for(form_id in form_ids) {
    meta = filter.data(manifest_data, "form_id", form_id);
    form_data = filter.data(data, "form_id", form_id);
    respondents = unique(form_data$respondent);

    form_dir_path = sprintf("%s/%s", output_dir, form_id);

    cat(sprintf("- %s (respondents: %d)\n   %s (%s %s)\n   %s\n", form_id, length(respondents), meta["course_name"], meta["course_period"], meta["course_modality"], meta["course_responsible"]));
    plot.form.data(form_data, form_dir_path, "", text_mode_questions);
}

cat(sprintf("Overall reports (%d forms in total)\n", length(form_ids)));

forms = unique(forms_data$form_id);
form_dir_path = sprintf("%s/%s", output_dir, "overall");

cat(sprintf("- %s (forms: %d)\n", filter, length(forms)));
plot.form.data(forms_data, form_dir_path, "", text_mode_questions);

cat(sprintf("Reports are done!\n"));
