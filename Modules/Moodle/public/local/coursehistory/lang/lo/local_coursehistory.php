<?php
// This file is part of Inteqc Company Configuaration 
//
// @package    local_coursehistory
// @copyright  2026
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

$string['pluginname']       = 'ປະຫວັດການຮຽນ';
$string['coursehistory']     = 'ປະຫວັດການຮຽນ';

// Form fields
$string['courseinfo']        = 'ຂໍ້ມູນຫຼັກສູດ';
$string['coursename']       = 'ຊື່ຫຼັກສູດ';
$string['coursename_help']  = 'ປ້ອນຊື່ເຕັມຂອງຫຼັກສູດທີ່ທ່ານໄດ້ຮຽນຈົບມາຈາກພາຍນອກ.';
$string['instructorname']   = 'ຊື່ອາຈານຜູ້ສອນ';
$string['organization']     = 'ສະຖາບັນ / ອົງກອນ';
$string['certificatefile']  = 'ໄຟລ໌ໃບຢັ້ງຢືນ';
$string['uploadcertificate']     = 'ອັບໂຫຼດໃບຢັ້ງຢືນ';
$string['uploadcertificate_help'] = 'ອັບໂຫຼດໃບຢັ້ງຢືນ ຫຼື ຫຼັກຖານການຮຽນຈົບຂອງທ່ານ. ຮູບແບບທີ່ຮອງຮັບ: PDF, JPG, PNG (ສູງສຸດ 5MB).';

// Actions
$string['submitcourse']     = 'ເພີ່ມຫຼັກສູດທີ່ຮຽນຈົບ';
$string['submitcourse_desc'] = 'ສົ່ງຫຼັກສູດທີ່ທ່ານໄດ້ຮຽນຈົບມາກ່ອນໜ້ານີ້. ກະລຸນາຕື່ມຂໍ້ມູນໃຫ້ຄົບຖ້ວນ ແລະ ອັບໂຫຼດໃບຢັ້ງຢືນຂອງທ່ານ.';
$string['approve']          = 'ອະນຸມັດ';
$string['reject']           = 'ປະຕິເສດ';
$string['view']             = 'ເບິ່ງ';

// Status
$string['status']           = 'ສະຖານະ';
$string['status_pending']   = 'ກຳລັງກວດສອບ';
$string['status_approved']  = 'ອະນຸມັດແລ້ວ';
$string['status_rejected']  = 'ປະຕິເສດແລ້ວ';

// Messages
$string['submitsuccess']    = 'ສົ່ງປະຫວັດການຮຽນສຳເລັດແລ້ວ! ຈະຖືກກວດສອບໂດຍຜູ້ດູແລລະບົບ.';
$string['submitsuccess_autoapproved'] = 'ສົ່ງປະຫວັດການຮຽນສຳເລັດແล້ว! ຈະຖືກກວດສອບໂດຍຜູ້ດູແລລະບົບ (ກົງກັບຫຼັກສູດ "{$a}")';
$string['submitsuccess_pending']      = 'ສົ່ງປະຫວັດການຮຽນສຳເລັດແລ້ວ! ບໍ່ພົບຫຼັກສູດທີ່ກົງກັນ — ຈະຖືກກວດສອບໂດຍຜູ້ດູແລລະບົບ.';
$string['coursematched']    = 'ຫຼັກສູດນີ້ກົງກັບຫຼັກສູດທີ່ມີຢູ່ໃນລະບົບ: "{$a}". (ຕ້ອງໄດ້ຮັບການກວດສອບ)';
$string['approved_success'] = 'ການສົ່ງຂໍ້ມູນໄດ້ຮັບການອະນຸມັດແລ້ວ.';
$string['rejected_success'] = 'ການສົ່ງຂໍ້ມູນຖືກປະຕິເສດແລ້ວ.';
$string['err_required']     = 'ຕ້ອງປ້ອນຂໍ້ມູນໃນຊ່ອງນີ້.';
$string['err_invalidfile']  = 'ໄຟລ໌ບໍ່ຖືກຕ້ອງ. ກະລຸນາອັບໂຫຼດໄຟລ໌ໃບຢັ້ງຢືນທີ່ຖືກຕ້ອງ (PDF, JPG ຫຼື PNG ເທົ່ານັ້ນ).';

// Review
$string['reviewsubmissions']       = 'ກວດສອບລາຍການທີ່ສົ່ງມາ';
$string['reviewedby_label']        = 'ກວດສອບໂດຍ';
$string['reviewcomment']           = 'ຄວາມຄິດເຫັນ';
$string['reviewcomment_placeholder'] = 'ເພີ່ມຄວາມຄິດເຫັນ (ບໍ່ບັງຄັບ)...';
$string['reviewactions']           = 'ການດຳເນີນການກວດສອບ';
$string['backtoreview']            = 'ກັບໄປໜ້າລາຍການກວດສອບ';
$string['backtohistory']           = 'ກັບໄປໜ້າປະຫວັດການຮຽນ';
$string['gotoreview']              = 'ໄປໜ້າກວດສອບ';

// Table & Profile
$string['learner']          = 'ຜູ້ຮຽນ';
$string['coursematch']      = 'ຄວາມກົງກັນຂອງຫຼັກສູດ';
$string['matched']          = 'ກົງກັນ';
$string['nomatch']          = 'ບໍ່ມີຫຼັກສູດທີ່ກົງກັນໃນລະບົບ';
$string['coursenotfound']   = 'ຫຼັກສູດທີ່ກົງກັນບໍ່ມີແລ້ວ';
$string['datesubmitted']    = 'ວັນທີສົ່ງ';
$string['actions']          = 'ການດຳເນີນການ';
$string['viewsubmission']   = 'ເບິ່ງຂໍ້ມູນທີ່ສົ່ງມາ';
$string['nosubmissions']    = 'ຍັງບໍ່ມີຂໍ້ມູນການຮຽນທີ່ສົ່ງມາ.';
$string['nofile']           = 'ບໍ່ມີການອັບໂຫຼດໄຟລ໌ໃບຢັ້ງຢືນ.';
$string['certificate_preview'] = 'ເບິ່ງຕົວຢ່າງໃບຢັ້ງຢືນ';

// Stats
$string['stat_total']       = 'ທັງໝົດ';
$string['stat_approved']    = 'ອະນຸມັດ';
$string['stat_pending']     = 'ລໍຖ້າ';
$string['stat_rejected']    = 'ປະຕິເສດ';

// Filters
$string['filter_all']       = 'ທັງໝົດ';
$string['filter_pending']   = 'ລໍຖ້າ';
$string['filter_approved']  = 'ອະນຸມັດແລ້ວ';
$string['filter_rejected']  = 'ປະຕິເສດ';

// Capabilities
$string['coursehistory:submit']  = 'ສົ່ງປະຫວັດການຮຽນພາຍນອກ';
$string['coursehistory:review']  = 'ກວດສອບການສົ່ງປະຫວັດການຮຽນ';
$string['coursehistory:viewall'] = 'ເບິ່ງປະຫວັດການຮຽນຂອງຜູ້ໃຊ້ທັງໝົດ';
