%-----------------------------------------------------------------------------
%
%               LaTeX Class/Style File
%
% Name:         Charité - Universitätsmedizin Berlin Repository Cover Sheet Template
%
% Purpose:      Cover sheet for the self-archiving of research publications,
%               deposited in a repository for the purpose of making the work
% 				open access. The template is offered by the Charité Medical
%               Library and is meant to be used by members of the
%				Charité - Universitätsmedizin Berlin.
%        
% Creator:      Thomas Vogel, Steffi Grimm, Elena Gandert
%
% Created:      June 2019
%
% Usage note: 	A Creative Commons license is not granted for this template because
%               of the corporate design and logo. But the code can be reused, in
%				case of questions please contact us at: openaccess@charite.de.
% 
%-----------------------------------------------------------------------------


\NeedsTeXFormat{LaTeX2e}
\ProvidesClass{charite}[2019/01/21 Class for title pages of Charite Berlin]

%% Options
% Language
\DeclareOption{english}	 { 
	\def \charite@docTypeHeader{Document type} 
	\def \charite@versionAvailableAtHeader{This version is available at}
	\def \charite@citationDetailsHeader{Citation details}
	\def \charite@termsOfUseHeader{Terms of use}
	\def \charite@yearOfPublicationHeader{Year of publication}
}

\DeclareOption*{\PassOptionsToClass{\CurrentOption}{memoir}}

\ExecuteOptions{english}
\ProcessOptions
\relax

\LoadClass[oldfontcommands, extrafontsizes, a4paper, oneside]{memoir}

%% Packages
\RequirePackage[dvipsnames, svgnames, cmyk]{xcolor}
\RequirePackage{tikz}
\RequirePackage{xspace}
\RequirePackage{pdfpages}
\RequirePackage{graphicx}
\usepackage[hidelinks]{hyperref}
\RequirePackage{url}
\urlstyle{sf}

%% Standalone
\RequirePackage{standalone}

%% Encoding
\RequirePackage[utf8]{inputenc}
\RequirePackage[T1]{fontenc}

%% Fonts and typography
\RequirePackage{lmodern}           % Latin Modern
\renewcommand{\sfdefault}{phv}     % Helvetica
\RequirePackage[scaled]{beramono}  % Bera Mono (Bitstream Vera Sans Mono)
\RequirePackage[final]{microtype}  % Improved typography


%% Charite colors
\definecolor{charitegray}{RGB}{160 165 169}
\definecolor{charitegrayarea}{RGB}{229 229 229}


%% Layout
\usepackage[a4paper]{geometry} % showframe

% Paper size
% offsets
%\hoffset=-0.34cm
\hoffset=-1in
%\voffset=-0.34cm
\voffset=-1in

% text area
\textwidth=166mm
\textheight=253mm

% header
\topmargin=22mm
\headsep=0mm
\headheight=0mm

% left margin
\oddsidemargin=22mm
\marginparpush=0mm

% right margin
\marginparwidth=0mm
\marginparsep=0mm

% footer
\footskip=0mm

% center without vspaces
\newenvironment{novspacecenter}
{\parskip=0pt\par\nopagebreak\centering}
{\par\noindent\ignorespacesafterend}


%% Title page
\newcommand{\charitetitle}
{
    \begin{titlingpage}
        	\begin{novspacecenter}
        		\includegraphics[width=150mm]{logo}
        	\end{novspacecenter}
            \vskip19mm
            \begin{tikzpicture}[remember picture, overlay]         
	        \fill [charitegrayarea, opacity=1] (-3.0,0.0) rectangle (19,-25);
	        \end{tikzpicture}
	        \raggedright
            \vskip18mm
             {
				\fontsize{22}{26.4}
				\bfseries
				\boldmath
				\sffamily
				\thetitle
				\par
            }
        	\ifx\charite@subtitle\empty
        		% no subtitle
        	\else
	            \vskip2\onelineskip
		        {
					\fontsize{20}{24}
					\boldmath
					\sffamily
					\charite@subtitle
					\par
		        }
	        \fi
            \vskip2.5\onelineskip
            {%
				\fontsize{15}{18}
				\boldmath
				\sffamily
				\theauthor
				\par
            }
            \vfill
            {
	            \large
	            %\fontsize{10}{12}
	            \sffamily
	            %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%	            
	            \ifx\charite@documentType\empty
		          	% no document type
	            \else
		            \textbf{\charite@docTypeHeader}\par
		            \charite@documentType
	            \fi             	
                %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
				\ifx\charite@versionAvailableAt\empty
					% no versionAvailableAt
				\else
					\vskip1.5\onelineskip
					\textbf{\charite@versionAvailableAtHeader}\par
					\charite@versionAvailableAt
				\fi
                %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
               	\ifx\charite@citationDetails\empty
               		% no citation details
               	\else
               		\vskip1.5\onelineskip
               		\textbf{\charite@citationDetailsHeader}\par
               		\charite@citationDetails
               	\fi
               	%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
               	\ifx\charite@yearOfPublication\empty
               		% no year of publication details
               	\else
               	\vskip1.5\onelineskip
               	\textbf{\charite@yearOfPublicationHeader}\par
               	\charite@yearOfPublication
               	\fi
               	%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
               	\ifx\charite@termsOfUse\empty
               		% no terms of use
               		\vskip1.5\onelineskip
               	\else
               		\vskip1.5\onelineskip
               	   	\textbf{\charite@termsOfUseHeader}\par
			   		\charite@termsOfUse
                \fi
                
				\vskip1\onelineskip
				\charite@publisherPhrase         
            }
    \end{titlingpage}
}


%% User-specified data
\newcommand{\subtitle}[1]{ \def \charite@subtitle{#1} }
\subtitle{}

\newcommand{\documentType}[1]{ \def \charite@documentType{#1} }
\documentType{}

\newcommand{\affiliation}[1]{ \def \charite@affiliation{#1} }
\affiliation{}

\newcommand{\versionAvailableAt}[1]{ \def \charite@versionAvailableAt{#1} }
\newcommand{\versionAvailableAtRefubium}[1]{\versionAvailableAt{\RefubiumURL{#1}}}
\newcommand{\versionAvailableAtDOI}[1]{\versionAvailableAt{\DOIURL{#1}}}
\newcommand{\versionAvailableAtURN}[1]{\versionAvailableAt{\URNURL{#1}}}
\versionAvailableAt{}

\newcommand{\citationDetails}[1]{ \def \charite@citationDetails{#1} }
\citationDetails{}

\newcommand{\yearOfPublication}[1]{ \def \charite@yearOfPublication{#1} }
\yearOfPublication{}

\newcommand{\termsOfUse}[1]{ \def \charite@termsOfUse{#1} }
\termsOfUse{}

\newcommand{\publisherPhrase}[1]{ \def \charite@publisherPhrase{#1} }
\publisherPhrase{}

%%%%%%%%%%%%%%

%% DOIs, URNs, etc.

%\RequirePackage{doi} % Ignores syntax of latex in DOI links
%\renewcommand{\doitext}{DOI:\space}

\makeatletter

\providecommand{\DOI}[1]{%
	\begingroup
	\let\bibinfo\@secondoftwo
	\urlstyle{sf}%
	\href{https://doi.org/#1}{%
		DOI:~\nolinkurl{#1}%
	}%
	\endgroup
}

\providecommand{\URL}[1]{%
	\begingroup
	\urlstyle{sf}%
	\href{#1}{%
		\nolinkurl{#1}%
	}%
	\endgroup
}

\providecommand{\DOIURL}[1]{%
	\URL{https://doi.org/#1}
}

\providecommand{\RefubiumURL}[1]{%	
	\URL{https://refubium.fu-berlin.de/handle/#1}
}

\providecommand{\URNURL}[1]{%
	\URL{http://nbn-resolving.de/#1}
}
\makeatother
%%%%%%%%%%%%%%

%%%%%%%%%%%%%%
% Licences
\newcommand{\AllRightsReserved}{All rights reserved. This document is intended solely for personal, non-commercial use.}
\newcommand{\CCBY}{This work is licensed under a Creative Commons Attribution 4.0 International license: \URL{https://creativecommons.org/licenses/by/4.0/}}
\newcommand{\CCBYSA}{This work is licensed under a Creative Commons Attribution-ShareAlike 4.0 International license: \URL{https://creativecommons.org/licenses/by-sa/4.0/}}
\newcommand{\CCBYND}{This work is licensed under a Creative Commons Attribution-NoDerivatives 4.0 International license: \URL{https://creativecommons.org/licenses/by-nd/4.0/}}
\newcommand{\CCBYNC}{This work is licensed under a Creative Commons Attribution-NonCommercial 4.0 International license: \URL{https://creativecommons.org/licenses/by-nc/4.0/}}
\newcommand{\CCBYNCSA}{This work is licensed under a Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International license: \URL{https://creativecommons.org/licenses/by-nc-sa/4.0/}}
\newcommand{\CCBYNCND}{This work is licensed under a Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International license: \URL{https://creativecommons.org/licenses/by-nc-nd/4.0/}}

%%%%%%%%%%%%%%
\endinput
