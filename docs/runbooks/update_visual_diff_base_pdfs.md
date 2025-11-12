# How to update the baseline pdfs for the visual diff checks

The visual diff checks run as part of the phpunit tests for service-pdf, have base pdfs to compare against. If these ever need updating, this can be done by :

In the test code, in phpstorm, put a breakpoint on the line that starts  :   $this->visualDiffCheck(  ,

In phpstorm, run the pdf-app-test in debug mode (same way as you would normally run the phpunit tests)

In docker dashboard open up the container that has a name like  opg-lpa-pdf-app-run-b4f60079cfcc, go to the exec tab
(Note that the name of this container changes on each run of the tests)

cd to /tmp

If you do a ls here you will see a pdf that's been generated (be sure that phpstorm has actually breakpointed, so that we're looking at the pdf for this test not a previous one)

Copy the pdf file to /app  (because you can't copy directly from /tmp)

On host machine, cd to service-pdf/tests/visualdiffpdfs dir where the existing pdfs are.

Do something like:   docker cp opg-lpa-pdf-app-run-9c26179280ad:/app/1762449985.0974-A510-7295-5715-Lpa120.pdf .
i:e  docker cp NAMEOFCONTAINERGOESHERE:/app/NAMEOFPDFFILEGOESHERE .

Might need to rename the file we've just copied to the name of the file we're actually replacing here

Repeat the above for each test that you need a new baseline file for 


