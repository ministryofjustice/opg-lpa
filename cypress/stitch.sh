#!/bin/bash
# Stitch together PF feature files 
cp cypress/integration/LpaTypePF.feature cypress/integration/StitchedCorrespondentPFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/DonorPF.feature >> cypress/integration/StitchedCorrespondentPFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/AttorneysPF.feature >> cypress/integration/StitchedCorrespondentPFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/ReplacementAttorneysPF.feature >> cypress/integration/StitchedCorrespondentPFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/CertProviderPF.feature >> cypress/integration/StitchedCorrespondentPFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/PeopleToNotifyPF.feature >> cypress/integration/StitchedCorrespondentPFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/InstructionsPreferencesPF.feature >> cypress/integration/StitchedCorrespondentPFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/SummaryPF.feature >> cypress/integration/StitchedCorrespondentPFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/ApplicantPF.feature >> cypress/integration/StitchedCorrespondentPFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/CorrespondentPF.feature >> cypress/integration/StitchedCorrespondentPFLpa.feature 
# Stitch together HW feature files 
cp cypress/integration/LpaTypeHW.feature cypress/integration/StitchedCorrespondentHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/DonorHW.feature >> cypress/integration/StitchedCorrespondentHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/AttorneysHW.feature >> cypress/integration/StitchedCorrespondentHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/ReplacementAttorneysHW.feature >> cypress/integration/StitchedCorrespondentHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/CertProviderHW.feature >> cypress/integration/StitchedCorrespondentHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/PeopleToNotifyHW.feature >> cypress/integration/StitchedCorrespondentHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/InstructionsPreferencesHW.feature >> cypress/integration/StitchedCorrespondentHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/SummaryHW.feature >> cypress/integration/StitchedCorrespondentHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/ApplicantHW.feature >> cypress/integration/StitchedCorrespondentHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/CorrespondentHW.feature >> cypress/integration/StitchedCorrespondentHWLpa.feature 
