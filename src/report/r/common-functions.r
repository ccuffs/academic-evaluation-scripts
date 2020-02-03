#
# This file contains several functions that are used by more
# than one script.
#

load.data <- function(file_path) {
    if (!file.exists(file_path)) {
    	stop(sprintf("[ERROR] File %s not found! \n", file_path), call.=FALSE);
    }

    # Load the subject's CSV
    data = read.csv(file_path, header=TRUE, sep=",", dec=".", as.is=TRUE, stringsAsFactors=FALSE);
    return(data);
}

# Filter a dataframe based on the value of a particular column.
filter.data <- function(data, column_name, column_value) {
    return(data[data[,column_name] == column_value,]);
}