# Change Log
All notable changes to this project will be documented in this file.

## [Unreleased]

## Version 5.0.5
 - bug : same tiers historized even if no soc change. 
   fix : historize function from agefodd_stagiaire_soc_history.php
         test on soc change    
          
        
## Version 5.0
- WARNING : The changes may create regressions for external modules with
  classes that extend `pdf_fiche_presence.modules.php`
- FIX tickets #11916, #11888, #11861 and #12049 : PDF templates for
  attendance sheets had wrong page break rules leading to orphans/widows
  and, in some cases, successions of pages containing one single cell
  overlapping the page break threshold [2020-12-24]

## Version 4.12

- FIX : Multicell html in PDF [2020-12-18]
- FIX : Dol V13 compatibility [2020-12-09]

___
## OLDER


***** ChangeLog for 4.9.12 compared to 4.9.11 *****
FIX : HTML 5 form validation for module external access view

***** ChangeLog for 3.0.18 compared to 3.0.17 *****
NEW : Can add image on location

***** ChangeLog for 3.0.17 compared to 3.0.16 *****
NEW : Display trainer type on trainer list
NEW : Option to Group session per day on session calendar
NEW : Add filter on session calendar on session status
NEW : Add option to warning or block if trainer is already book on another session

***** ChangeLog for 3.0.16 compared to 3.0.15 *****
NEW : Allow convention without financial document linked in module setup

***** ChangeLog for 3.0.15 compared to 3.0.14 *****
FIX : Various fix
NEW : new column for document models

***** ChangeLog for 3.0.14 compared to 3.0.13 *****
FIX : Global review for PgSQL

***** ChangeLog for 3.0.13 compared to 3.0.12 *****
NEW : Extrafields on trainee

***** ChangeLog for 3.0.12 compared to 3.0.11 *****
NEW : Joined Files on Trainne card
NEW : Compatible on Dolibarr 6.x
NEW : if cost management is disabled, no cost automatic update on session finacial document linked

***** ChangeLog for 3.0.11 compared to 3.0.10 *****
NEW : Add new right to acces module admin even if user is not dolibarr administrator

***** ChangeLog for 3.0.10 compared to 3.0.9 *****
NEW : Add chapter into convention

***** ChangeLog for 3.0.9 compared to 3.0.8 *****
NEW : Add admin option to include program into convention
NEW : Add admin option to include signature image into convocation
NEW : Add email tempates management with dictionnary

***** ChangeLog for 3.0.6 compared to 3.0.7 *****
NEW : Franch BPF 2017 available 
FIX : compatiblity for 5.0

***** ChangeLog for 3.0.5 compared to 3.0.6 *****
NEW : Better trainee category for french BPF 

***** ChangeLog for 3.0.4 compared to 3.0.5 *****
NEW : Certificate card format PDF
NEW : Add field on training that craate an QR code on credit card 

***** ChangeLog for 3.0.3 compared to 3.0.4 *****
NEW : Create event on trainer document send
NEW : Can send End training attestation into send doc  
FIX : Product cost management

***** ChangeLog for 3.0.2 compared to 3.0.3 *****
NEW : Add field on session for sous traitance
FIX : Allow to remove thirdparty and contact on session (if combox use)


***** ChangeLog for 3.0.1 compared to 3.0.2 *****
NEW : Add trainer attachement file tab
NEW : Add place attachement file tab
FIX : PDF, always use session duration instead of training programm duration
FIX : PDF, Always check Aquired on session objectifs on attestation end training 
NEW : PDF,  add chevalet documents


***** ChangeLog for 3.0.0 compared to 3.0.1 *****
FIX : Do not output chapter if empty into training program
NEW : Import/Export training program


***** ChangeLog for 2.1.16 compared to 3.0.0 *****
New : Add option to do not auto generate propal ref
Fix/New : Manage convention signataire in a better way for inter entreprise
New : Calendar tab to mamange separatly session calendar
New : is session salemans is empty, it will be affected automaticly to first customer saleman
New : Add pages numbers on all PDF documents
Fix : French translation
Fix : Add training program to proposal work not correctly
Fix : Issue #16
Fix : Issue #15
Fix : Issue #14
Fix : Issue #18
Fix : Issue #23
Fix : Issue #12
New : Can upload files on training (optionnaly to replace training program)
Fix : Invoice created from Document linked was not associated to Session
New : Add BPF report
New : Add {breakpage} tag into traiing program to mange manually break page on long program training
New : Add place control use in the same dates only for controled location
New : Only for Dolibarr 4.0.x version minimum
New : Better calendar session deletion management

***** ChangeLog for 2.1.15 compared to 2.1.14 *****
Fix : Fix training colors
Fix : Fix bug on new time session
Fix : Revert to php 5.4 compatiblity
Fix : Trainee status in session must not be auto updated if option is OFF on propal closure or re-opening
New : Export session with extrafields catalogue and session
New : Add place control use in the same dates 
New : Easier trainer calendar deletion (checkbox)

***** ChangeLog for 2.1.14 compared to 2.1.13 *****
-Fix : Fix logo on PDF
-Fix : Add option to output only product line on convention
-New : Add status DONE for training session
-New : Add colors on training
-New : Add modules in training catalogues
-New : Add file import of trainee into session
-New : Add trainer diploma management (dictionnay)
-New : Add option to link trainer  to training catalogue (to filter trainer list on trainer session affectation)
-New : New session calendar generation feature
-New : In session list display cost information (enabled by right management)
-New : Add End training attestation empty
-New : Can merge training programme to proposal PDF

***** ChangeLog for 2.1.13 compared to 2.1.12 *****
-Fix : Fix session agenda bug

***** ChangeLog for 2.1.12 compared to 2.1.11 *****
-New : Clone Training
-Fix : OPCA managemnt on intra
-New : Add trainee name into Document linked tabs (limit to 7 by company)
-New : Add more information into linked manually document by thridparty on Docuement linked tab
-New : can link manually document by thridparty and mother company on Docuement linked tab
-New : Add option to add avg cost on propal/order/invoice (also on update lines)
-New : Can link invoice/propal/session from session tab in propal/order/invoice screen
-New : PRESENCE SHEET : add trainer signature block by session calendar
-fix : decimal value in training catalogue duration allowed
-New : Add two field in training catalgue to be compliant with French DIRRECT 2015 reform (and output in fiche pedago PDF)
-New : Add recipient (supplier contact) in session (as well supplier contract and invoice)
-New : comptability for Dolibarr 3.7 only

***** ChangeLog for 2.1.11 compared to 2.1.10 *****
-Fix : Priority in objectif training can be new updated 
-Fix : Break pages on training program PDF
-Fix : Link Logistique go on location management
-New : Only for Dolibarr 3.6.x
-New : Trainer Mission letter

***** ChangeLog for 2.1.10 compared to 2.1.9 *****
-Fix : French spelling (thank to  Joël Pastré)
-Fix : Multicompany trainee creation can be block if trainee exist for another entity
-New : Add filter on site list
-Fix : Fix create trainne and add to session without linked to extra thirdparty
-Fix : Multi entity trainee (trainne is not sharable)
-Fix : Certificate Box show wrong result
-Fix : Edit administrative task into trainning show select parent task with multiples values
-New : New form to add trainee (from contact from simple creation are now mix in the same screen)
-New : Certificate A4 PDF with relevant data
-Fix : Remove certificate Credit card PDF  because not usefull
-New : Add trainer type on session. Usefull for french admnistrative document (Tools,Export)
-New : Can have different OPCA for one session/thirdparty in inter-company session


***** ChangeLog for 2.1.9 compared to 2.1.8 *****
-Fix : Attestation by trainnee with training objectives wrong output
-New : Better trainer list and session trainer list
-New : Attestation PDF is printed only if trainne is Cofrim/present or Part Present
-New : Add button to mass update trainee status in session (Edit trainee new button available)
-New : Convention per trainee (can create more than one convention per session/custom and affect them to selected trainnee)
-New : Clone trainer on session clone
-Fix : [bugs_agefodd #1433] Retreive document attached into session
-Fix : PDF Attestation by trainee Objectif pédagogique strange output
-Fix : bugs_agefodd #1453 - Erreur lors de l'attachement d'un OPCA sur un participant
-Fix : On Training program generated from session time was not correct.
-Fix : PDF output, global review on logo and other things
-New : New field on create trainee
-New : Better management of certification date according configuration
-New : Add location filter on agenda 
-Fix : Cost management create supplier invoice with product with no buying price wasn't ok

***** ChangeLog for 2.1.8 compared to 2.1.7 *****
-New : New permissions on Training catalogue, location (basic (old) permissions apply now sessions) all defaulted to yes (to avoid right problem)
-New : New permission on session to limit session display in list regarding user customer sales affectation
-Fix : Statistics screen account manager do not work
-Fix : Remove PHP warning on training note screen
-New : Can manage more than on proposal/order/invoice per session/thirdparty.
-Fix : bugs_agefodd #1143 - Apostrophe into trainee lastname or firstname 
-New : Cannot generate "auto-compelte" proposal from document link screen if no product linked before
-New : On Propal and order "auto" génération contacts are filled with session informations
-Fix : No-limit contact list if combobox is not use
-Fix : session : Send document better warining message and enabled WYSIWYG into mail message
-Fix : bugs_agefodd #1146 - small errors in fr_FR/agefodd.lang 
-Fix : On proposal or Order auto generation fill line with only on date if strat date and end date are the same 
-Fix/New : Propal/order/invoice session tab allow order and display Propal/Oerder/Inoice other tabs
-Change : Change status session list
-New : New left session menu
-Fix : Export Session didn't work if prefeix table were not llx_
-New : New tab "Trainer session list" into trainer screen
-New : If trainer calendar is use then control on existing booked date is done on update or add trainer calendar in session
-New : Warning message if session date are not correct regarding session calendar date (and trainer calendar date if use)
-New : color into Session agenda show status of session
-New : Session-> Document link => Cannot create invoice from proposal if proposal is not signed
-New : Session list can be filtered by period (1,2,3 in month and year=2013 will diplsay Jnuary,febuary,march 2013 session)
-New : Auto calculation of selling price of session (regarding proposal/order/invoicelinked to the session)
-New : Session cost mangement (supplier invoice creation from session) and auto caculation of session cost
-New : New field date for confirm reservation date on session
-New : If session is set to "Not done" status all status of trainer and trainee wil be set to cancelled
-New : if use trainer calendar date are dipslay in all card where trainer list is displayed
-New : Update certificate information from trainnee->certificate card
-New : Only for Dolibarr 3.5


For Dev : 
Column archive from llx_agefodd_session have been dropped now it's llx_agefodd_session.status=4

***** ChangeLog for 2.1.7 compared to 2.1.6 *****
For All:
	-Fix : Update session bug if no product set
	-Fix : Agenda session got Week and go to list file main.inc.php is missing
	-Fix : Trainer calendar in session decimal cost didn't save
	-Fix : Add trainee to session bug ( dol_time_plus_duree undefined function)
	-Fix : Problem with no use of trainee type into session
	-Fix : Various problem on Adress into letter PDF
	-New : Extend Training label to 100 caracters
For Dev:
	-New: Add column ref_ext into agefodd_session.

***** ChangeLog for 2.1.6 compared to 2.1.4 *****
For All:
	- New : Add session type into session list 
	- Fix : bugs_agefodd #1050 - Error when update trainee without using type
	- Fix : Update certificate with numbering rule always increment it
	- Fix : Field label Training internal ref vs ref
	- Fix : Use of Agefodd contact select list do not work.
	- New : Add trainer time management (optional in admnistration)
	- New : Add product link to session (copied from training is defined)
	- New : Add session status (and default status in administration)
	- New : Add menu session in draft status
	- New : Add button to generate convention directly from convention edit screen
	- New : When generate a document the screen auto scroll to targeted customer
	- New : Less click to add trainer
	- New : Add trainee name into proposal,order, invoice lines (optionnal in admnistration)
	- Fix : Specific agenda session and trainer session do not work well
	- New : When create trainee into session intra-entreprise the customer new trainne is already selected
	- Fix : Remove trainee from session decrement number of trainee into the session
	- Fix : PDF attestatation : missing space before hour
	- Fix : Session time selection to 23h (21h before)
	- New : If customer is specified on session header, if will be apply on calendar event 
	- New : Tab Training session on Thirdparty screen
	- Fix : Hide manage agefodd contact according admin setup
	- New : Session list by administrative task
	- New : PDF : Add function trainnee into convention and timesheet
	- New : Cerficate Box on module home page review (new dedicated list)

***** ChangeLog for 2.1.4 compared to 2.1.3 *****
For All:
	- Fix : When generating convention Main company information missing
	- Fix : Bad PDF Conseil pratique display when using WYSIWYG on training
	- Fix : Send Doc Welcome letter, convocation, courrier acceuil, conseil pratique did not work
	- New : Add filter on training list
	- New : New Session Export (can be use for BPF france)
	- Fix : Create invoice process block with 
	- Fix : Fix SQL upgrade bug (between 2.1.0 and 2.1.3 or 2.1.4) (no impact for orhter upgrade)
	- Upgrade : Dutch language file upgrade


***** ChangeLog for 2.1.3 compared to 2.1.2 *****
For Dev:
    - Change column name nb_min_target to nb_subscribe_min
For All    
    - Fix : pagging training list 
    - Fix : bugs_agefodd #1038 - Inscription participant
    - Fix : bugs_agefodd #1042 - Convention de formation
    - Fix : Objectif Pédagogique not display in trainning card and in Fiche pedago PDF.
    - New : Add training category manage by dictionnary.
    - New : Extrafield on training and session
    - New : Add tab list session on proposal
    
    
***** ChangeLog for 2.1.2 compared to 2.1.1 *****
For All :
    - Session : When create session failed field are refill with old value
    - Fix bug missing column creation

***** ChangeLog for 2.1.1 compared to 2.1.0 *****
For All :
    - Fix statistics pages (training list box can be empty)
    - Session Send docs : In send certificate add certificate A4 and certicificate card attachement files if certificate is managed (admin)
    - Fix : Doc timesheet presence (fiche de présence (format paysage)  

***** ChangeLog for 2.1.0 compared to 2.0.29 *****
For All :
    - Compatibility with Dolibarr 3.4 only
    - Fix bugs_agefodd #906 - Courrier accompagnant l'envoi du dossier de cloture   
    - tasks_agefodd #932 : Add calculation of the cost of a session
    - New Screen : Mass Archive sessions per year 
    - Import/Export Trainee 
    - Import/Export Certificate
    - Certificate : Certificate indicator (pass or failed)
    - Certificate : Certificate type dictionnary
    - Certificate : Certificate numbering rules
    - Dashboard : Fix dashboard bugs
    - Admin/Training/Session : Add administrative task per training (base on admin administrative task)
    - Session : Administrative task : Fix Admin administrative task (when all are deleted you can create new ones)
    - Admin : Switch to turn off OPCA (funding) feature
    - Session : Add session attached files tab (to attach all kind of document)
    - Session : Add product link to training
    - Session : Add proposal link to session
    - Session : Document linked screen : On create proposal auto create and link the full proposal
    - Session : Document linked screen : On create order auto create from proposal linked or from scratch
    - Session : Document linked screen : On create Invoice create and auto link to session/thridparty from order or proposal
    - Admin : Remove number of lines per list from agefodd admin because this option can be set into general Dolibarr administration and do conflict
    - Session : Calendar : Better input method in sessions from "templated date set" in admnistration screen
    - Session : Option to clone with trainee
    - Session : Subscribers have now a status (auto calculated or manually set) according administration options
    - Session list : Display ratio Prospect/confirm/cancelled in session list
    - Session list : Display ration in green if trainee confirm is equal or greater than minimum subscribers session information or red if not 
    - Session : Add proposal/order/invoice amount link to session
    - Agenda : Add custom agenda into Agefodd module to filter session by Salesman, thirdparty, contact, trainer
    - Agenda : Add custom agenda dedicated to trainer (if trainer logged in see only his own training session) managable by right management
For Dev:
    - Rewrite $line to $lines in all classes
    - Implement $lines object in all classes for fetch_all (or similar) methods
    - No more use of dol_htmloutput_mesg, uses setEventMessage instead
    - reformat (auto-indent) all module code
    - Remove dol_include_once when possible replace by require_once
    - Comment in code should be in english
    - Try to remove all PgSQL warning regarding date update or insert (date must be quoted)
    - Create class Agefodd_session_stagiaire and move all method concerning this class from Agsession class to Agefodd_session_stagiaire 


***** ChangeLog for 1.1 compared to 1.0 version *****
For developers:
- Fix: full Dolibarr 3.x compatibility
- Fix: complete restructuring of files

***** ChangeLog for 1.0-beta1 compared to non-existent version *****
For users:
- New: Agefodd Module Packaging.

***** ChangeLog for 2.0.0 compared to 1.0-beta1 *****
For all :
- Fix: full Dolibarr 3.2 compatibility
- Fix: complete restructuring of files

***** ChangeLog for 2.0.1 compared to 1.0-beta1 *****
For all :
- Fix: Bug on list Trainee and Contact 
- Fix: Add filter on Session and Trainee view

***** ChangeLog for 2.0.2 compared to 2.0.1 *****
For all :
- On Session edit page add button to save and close "edit mode" and another to save and stay in "edit" mode
- Add fields Site acess and various notes on place
- Add fields Required document and equipements on Trainning Catalogue
- Add PDF documents "conseils pratique"


***** ChangeLog for 2.0.3 compared to 2.0.2 *****
For all :
- Fix : When cancel trainner creation return on list is better than resturn on blank screen
- Fix : Suivi of place object (creator and creation date logged correctly)
- Fix : When cancel contact creation return on list is better than resturn on blank screen
- Fix : List of Dolibarr contact in contact Agefodd creation (display in gray (not selectable) contact already exists in agefodd)
- Fix : Suivie of trainneee code
- Fix : Add pseudo English translation
- Fix : Correct training update on session works

***** ChangeLog for 2.0.4 compared to 2.0.3 *****
For all :
- Fix : Can normally be use without "custom" directory

***** ChangeLog for 2.0.5 compared to 2.0.4 *****
For all :
- Fix : Add error massage to session update or new without location
- Fix : convention generation for number of trainne (Chapter 1)
- Fix : Set better PDF generation for documents.

***** ChangeLog for 2.0.6 compared to 2.0.5 *****
For all :
- Fix : convention PDF document city
- Fix : convention PDF page number
- Fix : convention generation predefined text
- Fix : fiche presence with more than 10 trainee

***** ChangeLog for 2.0.7 compared to 2.0.6 *****
For all :
- Fix : Document liée : generating invoice according and link to order
- Fix : Document liée : Better display (in one line) for "bon de commande"
- Fix : Pagging on session list (archive and active)
- Fix : Pagging on training list (archive and active)
- Fix : Pagging on site list (archive and active)
- Fix : Pagging on trainer list (archive and active)
- Fix : Pagging on training list (archive and active)
- Fix : Session edit detail display type of founding for trainee (if option enabled)
- Fix : Session time management (time by quarter rather than select date std control)

***** ChangeLog for 2.0.8 compared to 2.0.7 *****
For all :
- Fix : migration : location are correct after migration

***** ChangeLog for 2.0.9 compared to 2.0.8 *****
For all :
- Fix : Site, remove debug display userid
- Add : Session  :creation tool tips to explain from where come contact.
- Fix : Site, Edit and change location name
- Fix : Cenvetion PDF, Avoid create last page if "fiche pedagogique" not exist
- Fix : Site, Customer and supplier choice are possible
- Change : When creating site, it's no more possible to input adress. It's done just after on edit screen
- Change : Site : On edit screen, there is a button to import customer adress

***** ChangeLog for 2.0.10 compared to 2.0.9 *****
For all :
- Fix: Trainee - create : customer choice is now combobox
- Add: You can set in module conf if session contact come from agefodd contact or doibarr contact (if dolibarr contact, agefodd contact will be created auto)
- Change: Manage subscribe trainee on different tab in session screen 
For dev :
- Fix: Create agefodd contact return now new agefodd contact id.
- Fix: Session create : better error management 

***** ChangeLog for 2.0.11 compared to 2.0.10 *****
For dev :
- Fix: comment agefodd.lib.php according PSR 
For all :
- Fix : Session level graph now calulated on session information
- Fix : spelling correction
- Fix : Session : creation : better display of question mark (help picto)
- Fix : Session : document : convention on texte Texte "l'organisme" get good enterprise legal form 
- Fix : Session : document : convention text 5 correctly saved

***** ChangeLog for 2.0.12 compared to 2.0.11 *****
For all :
- Fix : Correct fiche pédagogique document (avoid bug of long programme and strange display)
- Fix : Remove programme document (useless because programme is include into fiche pédagogique)
- Fix : PDF Conseil pratique, add foot page
- Fix : PDF Convention : better Layout.
- Fix : PDF Convention : import all page of fiche pedago
- Fix : Allow into trainning programme double quote.
- Add : Generate fiche pedagogique from Trainning
- Add : PDF Réglement intérieur
- Add : Manage internal rule for location
- Add : Add technical spec into module folder
- Add : Agenda Dolibarr management
- Remove : BPF document because do not exists yet
- Fix : On update convention art. 5 is no more replace with art. 4
- Merge Branch from jf-Ferry : searate tab for trainner and merge subrogation and trainee
- Merge Branch from jf-Ferry : Type of session (intra-inter entreprise), session nb place, session color
For dev :
- Fix: Use __construct() for all class

***** ChangeLog for 2.0.13 compared to 2.0.12 *****
For all :
- Change: PDF : merge Reglement interieur et conseil pratrique
- Add : PDF : Add convocation model 
- Add : type attribut on training sessions
- Add : fields on training session 
- Add : allow to choice a color for the session
- Fix : On archive session with option "Affiche dans la liste des contacts (création de session) les contacts Dolibarr (plutot que les correspondant Agefodd)"
to yes, the contact client do not change anymore
- Fix : On desativation and reactivation of the module, only good upgrade script are launch.
- Add : Add field goal/but in training card and in PDF fiche Pedago
- Add : separate Tab for trainer
- Add : Send documents by mail (link with agenda)
- Add : better upgrade process (do not active session on upgrade version (must be from 2.0.12 to work)) 
For dev : 
 - always pass in paramters of method create, update, and so on, the $user object rather tahn $user->id
  - move all class to class directory
  
***** ChangeLog for 2.0.14 compared to 2.0.13 *****
For All :
   - Fix bug on updating session calendar
   - Fix upgrade process
For dev :
	- Delete AGF_LAST_VERION_INSTALL in modAgefodd before create a new one 
	
***** ChangeLog for 2.0.15 compared to 2.0.14 *****
For All :
   - Merge EnvoisDoc from jf-ferry
   - Manage error on trainee civility 
   - Correct Convention PDF Modele, now attach the correct fiche pedago
   - Review paging of Fiche pedago
   - Manage in convention the french TVA applicable or not (according conf->society setting)
   - Some chapter of convention are'nt no more retreive from older one, because some data can change from on session to another 
For Dev : 
   - Correct wrong call to agsession constructor in convention PDF
   - Drop llx_agefodd_place_ibfk_2 if extists because it is not use and create bug
   
***** ChangeLog for 2.0.16 compared to 2.0.15 *****
For All :
   - Merge EnvoieDoc from jf-ferry :
      - Add session list tab in trainning and site screen
      - Change some description into document screen
      - improvement of envoie doc screen and better mail layout
   - Correct the litteral number of page for the convention PDF
   - correct some syntax in PDF
   - add parameters in admin to change number of elements display in all list screen
   - Correct town trainning format in agefodd configuration 
   - Change type of but, prerequis, public, method for extand to text (more than 255 caracters)
   - Correct mistake into fiche presence
   - Add color picker for PDF model in configuration
For Dev : 
   - Rename file update_1.0-2.0 to avoid this file to be run. Use this file only for migration from Agefodd for Dolibarr 2.9 to this version
   - Remove "Plateau technique" from PDF 
   
***** ChangeLog for 2.0.17 compared to 2.0.16 *****
For All :
	- Fix Bug on Universal mask (Bug #541)
	- Uniformize pagefoot for PDF
	- Display subrogation info in all case
	- Merge from jf-ferry : multicompagny module compatibility
For Dev : 
	- Add entity column to prepare multicompany module functionnality
	- change way to get society info in PDF use $mysoc global is better than $conf->global->MAIN_SOC....
	
***** ChangeLog for 2.0.18 compared to 2.0.17 *****
For All :
	- Can create trainer from Dolibarr user list
	- Add ref interne on Training catalogue
	- Add Multicompany for trainer,catalogue, contact (correspondant)
	- Location : Fix bug on creation of internal Rule
	- Clone session
For Dev : 
	- Add Multicompany for catalogue (fetch method)
	- remove llx_agefodd_reg_interieur foreign key to allow Internal Rule creation. Manage foreign key by code
	
***** ChangeLog for 2.0.19 compared to 2.0.18 *****
For All :
	- Add option from jf-ferry to add picture of customer on doc
	- Add missing english translation
	- Fix behaviour on force update number of trainee per session
	- Fix bug #582 on fiche pedage (duration is not display) 
	- Review adn improve of trainee creation page
	- Functionnal documentation updated
	- Option to link invoice without order
	
***** ChangeLog for 2.0.20 compared to 2.0.19 *****
For All :
	- review page title (tag <TITLE>)
	- Fix Bug #589 on PDF Asttestation (duration  and objectif are not display)
	- Task #591 complete (PDF foot page render only fill information)
	- Add translation for index pages and module titles
	- Change behaviour of function "auto calcul nb trainne" in session card
	- Better calculation of nb trainee trained in first stat pages
	- Better comptatibility module for multicompny
	- Fix : Sort of list place
	- Better error management in place update screen
	- Display all compagny link to a session in Document screen (Customer session, OPCA trainee, OPCA session)
		- you can now generate Convention for OPCA or Customer as you wish
	- Add translation key for all PDF and convention
	- Add tab Session in Order and Invoice screen  
	- Fix bug on trainee creation (always create contact of customer) 
	- Task #512 complete (title of convention is trainee company is individual)
For Dev:
	- Rename index on tables to be unique in all dolibarr database 
	- Save correct convention creation date in formation catalogue and objectif peda
	- Set correct creation date in create stagaire_type sql script
	- Fix some query for PostgreSQL compatibility
	- Review creation table SQL script to be complient with Dolibarr and PostgreSQL query	
	
	
***** ChangeLog for 2.0.21 compared to 2.0.20 *****
For All :
	- Add option to activate MAIN_USE_COMPANY_NAME_OF_CONTACT in admin (see help for detail)
	- Add statistique blok (thank to jf-ferry)
	- Fix bug #613 - erreur lors du "clonage de session"
	- Fix bug #614 - bug affichage sur convention
	- Fix bug #612 - Contact sur courrier accompagnant l'envoi du dossier de clôture


***** ChangeLog for 2.0.22 compared to 2.0.21 *****
For All :
	- Better installation/upgrade process (run only structure update required)
	- Change database structure to upgrade performance and reliability
	- Add postgresql compatibility
	- Index (first figures page): Fix lots of bugs on figures
	- Session : On create Session contact is refresh automaticly on customer selection(according to settings (show Dolibarr contact in creation of session=>Yes,Thirdsparty settings combox-box=>Yes))
	- Session : display trainnee funding only if option activated
	- Session : Add option to display contact OPCA adress rather than OPCA adress
	- Session : Fix bug on add trainer : if trainne is a user, it's now possible to select it (bug occured if combo-box for trainer is not active) 
	- Session : Fix Bug on create session : Customer contact is correctly save
	- Session : Fix Bug on update calendar : new date correctly saved
	- Index (first figures page): Add english missings translations
	- Session Calendar/admin : Task #450 - Crée une journée type et permettre de la choisir sur la gestion du calendrier de la session
	- PDF attestation : Task #623 - Attestation de formation
	- PDF convetion : task #615 - Première page convention de formation
	- PDF customization : tasks #650 - 2 more color selector for PDF customization (thanks to sgiovagnoli for his contribution)
	- PDF timecard (fiche presence) : tasks #649 - PDF Timecard (fiche presence) behaviours
For Dev :
	- Review function comment for all class
	- Review $this->line[$i]->... in some class to avoid "create object from empty property" if display PHP error message is ON  
	- Move multiselect directory from inc/multiselect to includes/multiselect
	- Add about pages 
	- Enabled required other Dolibarr modules on Agefodd activation 
	
***** ChangeLog for 2.0.23 compared to 2.0.22 *****
For All :
	- Add Timecard in landscape format
	- Fix bug 677,678,679,680,682
	- Task tasks_agefodd #688 done - 	Add litte session header in Session->document tabs
	- Add Convocation send document behaviours
	- Fix translation in Send documents screen 
	- tasks_agefodd #687 - Send convocation 
For Dev :
	- Update licence version from GPL v2 to GPL v3
	
***** ChangeLog for 2.0.24 compared to 2.0.23 *****
For All :
	- Fix logo size and localization on PDF
	- Add Dutch language file (Thanks to S. van Tuinen)
	- Fix Bug 716 (Now in Session->Send docs : only trainee from contact email will be available in list box)
	- Fix Bug on "courrier" PDF
	- Add Send Doc "Conseil pratique"
	- Fix bug in Send Documents screen (document not attached, event not triggred...)
	- Add ComboBox select in New trainer card
	- Add behaviours "Certification" (task #616)
	
***** ChangeLog for 2.0.25 compared to 2.0.24 *****
For All :
	- Fix bugs_agefodd #721
	- Fix bugs_agefodd #723
	- Fix bugs_agefodd #724
	
***** ChangeLog for 2.0.26 compared to 2.0.25 *****
For All :
	- Change PDF Fiche presence: remove double country displayed in header
	- Fix bugs_agefodd #660
	- Fix bugs_agefodd #684
	- Session : Color picker reflect immediatly the color changes
	- WYSIWYG in trainning card (activated by option in configuration)
	- Fix oversize problem for Timecard by trainee (Fiche de présence, Fiche de présence vide, Fiche de présence (format paysage), Fiche de présence par stagaire)
For Dev : 
	- Change colorpicker lib js to avoid js bug (can be seen with firebug)
	
***** ChangeLog for 2.0.27 compared to 2.0.26 *****
For All :
	- PDF : Fix Fiche pedago problem (footer on two pages...)
	- PDF : Fix Customer image into certification (attestation) and timecard (feuille présence)
	- Admin page with on/off button
	- Add more Dutch translation and English also.
For dev : 
	-Change admin page from agefodd.php to admin_agefodd.php
	
***** ChangeLog for 2.0.28 compared to 2.0.27 *****
For All :
	- Fix bugs_agefodd #760
	- Fix bugs_agefodd #761
	- PDF : Fiche eval : Litle update
	- Session/Send Documents : Fix history event view problem with dolibarr 3.3
	- PDF : Convention : On order summary the product description are display.
	- Review English and Dutch translation
	- Remove PHP warining from agefodd_facture and agefodd_session_calendrier
	
***** ChangeLog for 2.0.29 compared to 2.0.28 *****
For All :
	- Fix french translation (spelling)
	- Fix Bug #829 - Can't clone a session (with pgsql)
	- Fix bug PDF : If use customer logo and the logo do not exists PDF do not print at all
	- Fix bug bugs_agefodd #868 - bugs envoi mail attestation
	
***** ChangeLog for 2.0.30 compared to 2.0.29 *****
For All :
	- Fix bugs_agefodd #897 - PDF Conseil Pratique do not result proper data with 3.3
	- Fix bugs_agefodd #896 - PDF header ugly if no logo on company
	- Fix bugs_agefodd #898 - "Suivie admnistratif" this screen simply do not provide relevent information 
	
