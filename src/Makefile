.PHONY: target1   # Target 1 help text
target1: deps
	ls

.PHONY: target2   # Target 2 help text
target2:
	ls

.PHONY: help      # Generate list of targets with descriptions                                                                
help:                                                                                                                    
	@grep '^.PHONY: .* #' Makefile | sed 's/\.PHONY: \(.*\) # \(.*\)/\1 \2/' | expand -t20