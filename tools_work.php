<?php
require_once("docutil.php");
page_head("Generating work");
echo "

As described earlier, a <a href=work.php>workunit</a>
represents the inputs to a computation.
The steps in creating a workunit are:
<ul>

<li> Write XML 'template files' that describe the workunit
and its corresponding results.
Generally the same templates will be used for
a large number work of workunits.

<li> Create the workunit's input file(s)
and place them in the download directory.

<li> Invoke a BOINC function or script that creates a
database record for the workunit.

</ul>
Once this is done, BOINC takes over:
it creates one or more results for the workunit,
distributes them to client hosts,
collects the output files,
finds a canonical result,
assimilates the canonical result,
and deletes files.

<p>
During the testing phase of a project,
you can use the <a href=busy_work.php>make_work</a> daemon
to replicate a given workunit as needed to maintain
a constant supply of work.
This is useful while testing and debugging the application.


<h2>Workunit and result template files</h2>
<a name=wu_template></a>
<p>
A workunit template file has the form
<pre>",htmlspecialchars("
<file_info>
    <number>0</number>
    [ <sticky/>, other attributes]
</file_info>
[ ... ]
<workunit>
    <file_ref>
        <file_number>0</file_number>
        <open_name>NAME</open_name>
    </file_ref>
    [ ... ]
    [ <command_line>-flags xyz</command_line> ]
    [ <rsc_fpops_est>x</rsc_fpops_est> ]
    [ <rsc_fpops_bound>x</rsc_fpops_bound> ]
    [ <rsc_memory_bound>x</rsc_memory_bound> ]
    [ <rsc_disk_bound>x</rsc_disk_bound> ]
    [ <delay_bound>x</delay_bound> ]
    [ <min_quorum>x</min_quorum> ]
    [ <target_nresults>x</target_nresults> ]
    [ <max_error_results>x</max_error_results> ]
    [ <max_total_results>x</max_total_results> ]
    [ <max_success_results>x</max_success_results> ]
    [ <credit>X</credit> ]
</workunit>
"), "
</pre>
The components are: 
";
list_start();
list_item_func("<file_info>, <file_ref>",
    "Each pair describes an <a href=files.php#file>input file</a>
    and <a href=files.php#file_ref>the way it's referenced</a>."
);
list_item_func("<command_line>",
    "The command-line arguments to be passed to the main program."
);
list_item_func("<credit>",
    "The amount of credit to be granted for successful completion
    of this workunit.
    Use this only if you know in advance
    how many FLOPs it will take.
    Your <a href=validate_simple.php>validator</a> must
    use get_credit_from_wu() as its compute_granted_credit() function."
);
list_item("Other elements",
    "<a href=work.php>Work unit attributes</a>"
);
list_end();
echo"
Workunit database records include a field, 'xml_doc',
that is an XML-format description of the workunit's input files.
This is derived from the workunit template as follows:
<ul>
<li>
Within a &lt;file_info> element,
&lt;number>x&lt;/number> identifies the order of the file.
It is replaced with elements giving
the filename, download URL, MD5 checksum, and size.
<li>
Within a &lt;file_ref> element,
&lt;file_number>x&lt;/file_number> is replaced with an element
giving the filename.
</ul>

<a name=result_template></a>
<p>
A result template file has the form
<pre>", htmlspecialchars("
<file_info>
    <name><OUTFILE_0/></name>
    <generated_locally/>
    <upload_when_present/>
    <max_nbytes>32768</max_nbytes>
    <url><UPLOAD_URL/></url>
</file_info>
<result>
    <file_ref>
        <file_name><OUTFILE_0/></file_name>
        <open_name>result.sah</open_name>
    </file_ref>
</result>
"), "</pre>
<p>
Result database records include a field, 'xml_doc_in',
that is an XML-format description of the result's output files.
This is derived from the result template as follows:
<ul>
<li>
&lt;OUTFILE_n> is replaced with a string of the form
'wuname_resultnum_n' where wuname is the workunit name and resultnum is
the ordinal number of the result (0, 1, ...).
<li>
&lt;UPLOAD_URL/> is replaced with the upload URL.
</ul>
<p>

<h2>Moving input files to the download directory</h2>

If you're using a flat download directory,
just put input files in that directory.
If you're using <a href=hier_dir.php>hierarchical upload/download directories</a>,
you must put each input file in the appropriate directory;
the directory is determined by the file's name.
To find this directory, call the C++ function
<pre>
dir_hier_path(
    const char* filename,
    const char* root,       // root of download directory
    int fanout,             // from config.xml
    char* result,           // path of file in hierarchy
    bool create_dir=false   // create dir if it's not there
);
</pre>
If you're using scripts, you can invoke the program
<pre>
dir_hier_path filename
</pre>
It prints the full pathname and creates the directory if needed.
Run this in the project's root directory.
For example:
<pre>
cp test_workunits/12ja04aa `bin/dir_hier_path 12ja04aa`
</pre>
copies an input file from the test_workunits directory
to the download directory hierarchy.


<h2>Creating workunit records</h2>
<p>
Workunits can be created using either a script
(using the <code>create_work</code> program)
or a program (using the <code>create_work()</code> function).
The input files must already be in the download hierarchy.

<p>
The utility program is
<pre>
create_work
    -appname name                       // application name
    -wu_name name                       // workunit name
    -wu_template filename               // WU template filename
        // relative to project root; usually in templates/
    -result_template filename           // result template filename
        // relative to project root; usually in templates/
    [ -batch n ]
    [ -priority n ]

    // The following may be passed in the WU template,
    // or as command-line arguments to create_work,
    // or not passed at all (defaults will be used)

    [ -command_line \"-flags foo\" ]
    [ -rsc_fpops_est x ]
    [ -rsc_fpops_bound x ]
    [ -rsc_memory_bound x ]
    [ -rsc_disk_bound x ]
    [ -delay_bound x ]
    [ -min_quorum x ]
    [ -target_nresults x ]
    [ -max_error_results x ]
    [ -max_total_results x ]
    [ -max_success_results x ]
    [ -additional_xml 'x' ]

    infile_1 ... infile_m           // input files
</pre>
The program must be run in the project root directory.
The workunit parameters are documented <a href=work.php>here</a>.
The -additional_xml argument can be used to supply, for example,
&lt;credit>12.4&lt;/credit>.

<p>
BOINC's library (backend_lib.C,h) provides the functions:
<pre>
int create_work(
    DB_WORKUNIT&,
    const char* wu_template,                  // contents, not path
    const char* result_template_filename,     // relative to project root
    const char* result_template_filepath,     // absolute or relative to current dir
    const char** infiles,                     // array of input file names
    int ninfiles
    SCHED_CONFIG&,
    const char* command_line = NULL,
    const char* additional_xml = NULL
);
</pre>
<p>
<b>create_work()</b> creates a workunit.
The arguments are similar to those of the utility program;
some of the information is passed in the DB_WORKUNIT structure,
namely the following fields:
<pre>
name
appid
</pre>
The following may be passed either in the DB_WORKUNIT structure
or in the workunit template file:
<pre>
rsc_fpops_est
rsc_fpops_bound
rsc_memory_bound
rsc_disk_bound
batch
delay_bound
min_quorum
target_nresults
max_error_results
max_total_results
max_success_results
</pre>

<h2>Examples</h2>
<h3>Making one workunit</h3>
<p>
Here's a program that generates one workunit
(error-checking is omitted for clarity):
"; block_start(); echo "
#include \"backend_lib.h\"

main() {
    DB_APP app;
    DB_WORKUNIT wu;
    char wu_template[LARGE_BLOB_SIZE];
    char* infiles[] = {\"infile\"};

    SCHED_CONFIG config;
    config.parse_file();

    boinc_db.open(config.db_name, config.db_host, config.db_passwd);
    app.lookup(\"where name='myappname'\");

    wu.clear();     // zeroes all fields
    wu.appid = app.id;
    wu.min_quorum = 2;
    wu.target_nresults = 2;
    wu.max_error_results = 5;
    wu.max_total_results = 5;
    wu.max_success_results = 5;
    wu.rsc_fpops_est = 1e10;
    wu.rsc_fpops_bound = 1e11;
    wu.rsc_memory_bound = 1e8;
    wu.rsc_disk_bound = 1e8;
    wu.delay_bound = 7*86400;
    read_filename(\"templates/wu_template.xml\", wu_template, sizeof(wu_template));
    create_work(
        wu,
        wu_template,
        \"templates/results_template.xml\",
        \"templates/results_template.xml\",
        infiles,
        1,
        config
    );
}
"; block_end(); echo "

This program must be run in the project directory
since it expects to find the config.xml file in the current directory.

<h3>Making lots of workunits</h3>

<p>
If you're making lots of workunits
(e.g. to do the various parts of a parallel computation)
you'll want the workunits to differ either in
their input files, their command-line arguments, or both.

<p>
For example, let's say you want to run a program
on ten input files 'file0', 'file1', ..., 'file9'.
You might modify the above program with the following code:
"; block_start(); echo "
    char filename[256];
    char* infiles[1];
    infiles[0] = filename;
    ...
    for (i=0; i<10; i++) {
        sprintf(filename, \"file%d\", i);
        create_work(
            wu,
            wu_template,
            \"templates/results_template.xml\",
            \"templates/results_template.xml\",
            infiles,
            1,
            config
        );
    }
"; block_end(); echo "
Note that you only need one workunit template file
and one result template file.

<p>
Now suppose you want to run a program against
a single input file, but with ten command lines,
'-flag 0', '-flag 1', ..., '-flag 9'.
You might modify the above program with the following code:
"; block_start(); echo "
    char command_line[256];
    ...
    for (i=0; i<10; i++) {
        sprintf(command_line, \"-flag %d\", i);
        create_work(
            wu,
            wu_template,
            \"templates/results_template.xml\",
            \"templates/results_template.xml\",
            infiles,
            1,
            config,
            command_line
        );
    }
"; block_end(); echo "
Again, you only need one workunit template file
and one result template file.
";
    
page_tail();
?>
