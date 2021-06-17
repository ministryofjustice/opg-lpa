#!/bin/bash
# Stitch together PF feature files 
cat cypress/integration/LpaTypePF.feature | sed "s/@CreateLpa/@StitchedPF/" > cypress/integration/StitchedCreatePFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/DonorPF.feature >> cypress/integration/StitchedCreatePFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/AttorneysPF.feature >> cypress/integration/StitchedCreatePFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/ReplacementAttorneysPF.feature >> cypress/integration/StitchedCreatePFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/CertProviderPF.feature >> cypress/integration/StitchedCreatePFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/PeopleToNotifyPF.feature >> cypress/integration/StitchedCreatePFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/InstructionsPreferencesPF.feature >> cypress/integration/StitchedCreatePFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/SummaryPF.feature >> cypress/integration/StitchedCreatePFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/ApplicantPF.feature >> cypress/integration/StitchedCreatePFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/CorrespondentPF.feature >> cypress/integration/StitchedCreatePFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/WhoAreYouPF.feature >> cypress/integration/StitchedCreatePFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/RepeatApplicationPF.feature >> cypress/integration/StitchedCreatePFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/FeeReductionPF.feature >> cypress/integration/StitchedCreatePFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/CheckoutPF.feature >> cypress/integration/StitchedCreatePFLpa.feature 
# Stitch together HW feature files 
cat cypress/integration/LpaTypeHW.feature | sed "s/@CreateLpa/@StitchedHW/" > cypress/integration/StitchedCreateHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/DonorHW.feature >> cypress/integration/StitchedCreateHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/AttorneysHW.feature >> cypress/integration/StitchedCreateHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/ReplacementAttorneysHW.feature >> cypress/integration/StitchedCreateHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/CertProviderHW.feature >> cypress/integration/StitchedCreateHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/PeopleToNotifyHW.feature >> cypress/integration/StitchedCreateHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/InstructionsPreferencesHW.feature >> cypress/integration/StitchedCreateHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/SummaryHW.feature >> cypress/integration/StitchedCreateHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/ApplicantHW.feature >> cypress/integration/StitchedCreateHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/CorrespondentHW.feature >> cypress/integration/StitchedCreateHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/WhoAreYouHW.feature >> cypress/integration/StitchedCreateHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/RepeatApplicationHW.feature >> cypress/integration/StitchedCreateHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/FeeReductionHW.feature >> cypress/integration/StitchedCreateHWLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/CheckoutHW.feature >> cypress/integration/StitchedCreateHWLpa.feature 
# Stitch together PF Clone feature files 
cat cypress/integration/LpaTypePFClone.feature | sed "s/@CreateLpa/@StitchedPFClone/" > cypress/integration/StitchedClonePFLpa.feature 
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/DonorPF.feature >> cypress/integration/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/AttorneysPF.feature >> cypress/integration/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/ReplacementAttorneysPFClone.feature >> cypress/integration/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/CertProviderPF.feature >> cypress/integration/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/PeopleToNotifyPFClone.feature >> cypress/integration/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/InstructionsPreferencesPF.feature >> cypress/integration/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/SummaryPFClone.feature >> cypress/integration/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/ApplicantPF.feature >> cypress/integration/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/CorrespondentPF.feature >> cypress/integration/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/WhoAreYouPF.feature >> cypress/integration/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/RepeatApplicationPF.feature >> cypress/integration/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/FeeReductionPF.feature >> cypress/integration/StitchedClonePFLpa.feature
awk '/needed for stitching/,0{if (!/needed for stitching/)print}' < cypress/integration/CheckoutPF.feature >> cypress/integration/StitchedClonePFLpa.feature
