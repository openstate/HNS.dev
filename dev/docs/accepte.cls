\NeedsTeXFormat{LaTeX2e}
\ProvidesClass{accepte}
\DeclareOption*{\PassOptionsToClass{\CurrentOption}{article}}
\ProcessOptions\relax
\LoadClass{article}

%\RequirePackage{color}
\RequirePackage{fancyhdr}
\RequirePackage{lastpage}
\RequirePackage{graphics}
\RequirePackage{fullpage}


\ifx\pdfoutput\@undefined\usepackage[usenames,dvips]{color}
\else\usepackage[usenames,dvipsnames]{color}
% and fix pdf colour problems
\IfFileExists{pdfcolmk.sty}{\usepackage{pdfcolmk}}{} 
\fi

\let\LaTeX@startsection\@startsection
% Accepte groen: rgb 153,204,0 = 0.6,0.8,0
\definecolor{accepte} {rgb}{0.6,0.8,0}
\definecolor{paylogic}{rgb}{0.97,0.58,0.11}
\definecolor{documentcolor}{rgb}{0.6,0.8,0}

\renewcommand{\@startsection}[6]{\LaTeX@startsection{#1}{#2}{#3}{#4}{#5}{\color{documentcolor}\raggedright #6}}
\renewcommand{\familydefault}{\sfdefault}

\pagestyle{fancy}
\headheight 22pt
\headsep 10pt
\fancyhf{}
\fancyhead[LO]{\nouppercase{\leftmark}}
\fancyhead[RO]{\resizebox{!}{18pt}{\includegraphics{Accepte.pdf}}}
\fancyfoot[RO]{\thepage /\pageref{LastPage}}
\fancyfoot[LO]{\textcolor{documentcolor}{Accept\'e BV} - \@title}
\renewcommand{\footrulewidth}{0.4pt}

\newcommand{\email}[1]{\providecommand{\@email}{#1}}
\newcommand{\phone}[1]{\providecommand{\@phone}{#1}}

\renewcommand{\maketitle}{\begin{titlepage}
		\setlength{\unitlength}{1mm}
		\begin{picture}(0,0)(31,299)
			\put(0,  0){\colorbox{documentcolor}{\makebox(15,84.5){}}}
			\put(0, 88){\colorbox{documentcolor}{\makebox(15,181.5){}}}
			\put(0,273){\colorbox{documentcolor}{\makebox(15,14.5){}}}
			\put(0,291){\colorbox{documentcolor}{\makebox(15,50){}}}
			\put(0,  0){\colorbox{documentcolor}{\makebox(4,300){}}}
		\end{picture}
		\begin{center}
			\resizebox{4in}{!}{\includegraphics{Accepte.pdf}}
			\vskip 1in
			{\color{documentcolor} \Huge \bf \@title}
		\end{center}
		\vfill
		\small
		\begin{tabular}{l@{\hspace{1in}}l}
		{\color{documentcolor} \normalsize Accept\'e B.V.} & \\
		Grote Markt 26    & Keizersgracht 261 \\
		9712 HV Groningen & 1016 EC Amsterdam \\
		088 7000 100      & 020 890 4428\\
		\\
		{\color{documentcolor} www.accepte.nl}  & {\color{documentcolor} \@author}\\
		{\color{documentcolor} info@accepte.nl} & \@phone\\
		                                        & {\color{documentcolor} \@email}
		\end{tabular}
		\vskip 1cm
		\noindent
		\@date
	\end{titlepage}

	\global\let\maketitle\relax
	%\global\let\@title\@empty
	\global\let\@author\@empty
	\global\let\@date\@empty
	
	\global\let\@phone\@empty
	\global\let\@email\@empty

	\global\let\title\relax
	\global\let\author\relax
	\global\let\date\relax

	\global\let\phone\relax
	\global\let\email\relax
}