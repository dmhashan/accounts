<?php

namespace Database\Seeders;

use App\Models\FormTemplate;
use App\Models\Tenant;
use App\Services\FormBuilderService;
use Illuminate\Database\Seeder;

class ParqFormTemplateSeeder extends Seeder
{
    // Stable field IDs so translations remain consistent on every seeder run
    private const F_HEADING = 'parq-f-heading';

    private const F_PARA_AGE = 'parq-f-para-age';

    private const F_PARA_INTRO = 'parq-f-para-intro';

    private const F_Q1 = 'parq-f-q1';

    private const F_Q2 = 'parq-f-q2';

    private const F_Q3 = 'parq-f-q3';

    private const F_Q4 = 'parq-f-q4';

    private const F_Q5 = 'parq-f-q5';

    private const F_Q6 = 'parq-f-q6';

    private const F_Q7 = 'parq-f-q7';

    private const F_PARA_NOTES = 'parq-f-para-notes';

    private const F_PARA_DECL = 'parq-f-para-decl';

    private const F_SIGNATURE = 'parq-f-signature';

    private const F_DATE = 'parq-f-date';

    public function run(): void
    {
        /** @var FormBuilderService $formBuilder */
        $formBuilder = app(FormBuilderService::class);

        $fields = [
            [
                'id' => self::F_HEADING,
                'type' => 'heading',
                'label' => 'Physical Activity Readiness Questionnaire (PAR-Q)',
                'required' => false,
            ],
            [
                'id' => self::F_PARA_AGE,
                'type' => 'paragraph',
                'label' => 'A Questionnaire for People Aged 15 to 69',
                'required' => false,
            ],
            [
                'id' => self::F_PARA_INTRO,
                'type' => 'paragraph',
                'label' => 'Regular physical activity is fun and healthy, and increasingly more people are starting to become more active every day. Being more active is very safe for most people. However, some people should check with their doctor before they start becoming much more physically active.'
                    . "\n\n"
                    . 'If you are planning to become much more physically active than you are now, start by answering the seven questions below. If you are between the ages of 15 and 69, the PAR-Q will tell you if you should check with your doctor before you start. If you are over 69 years of age and are not used to being very active, check with your doctor.'
                    . "\n\n"
                    . 'Common sense is your best guide when you answer these questions. Please read the questions carefully and answer each one honestly: answer YES or NO.',
                'required' => false,
            ],
            [
                'id' => self::F_Q1,
                'type' => 'radio',
                'label' => '1. Has your doctor ever said that you have a heart condition AND that you should only do physical activity recommended by a doctor?',
                'required' => true,
                'options' => ['Yes', 'No'],
            ],
            [
                'id' => self::F_Q2,
                'type' => 'radio',
                'label' => '2. Do you feel pain in your chest when you do physical activity?',
                'required' => true,
                'options' => ['Yes', 'No'],
            ],
            [
                'id' => self::F_Q3,
                'type' => 'radio',
                'label' => '3. In the past month, have you had chest pain when you were not doing physical activity?',
                'required' => true,
                'options' => ['Yes', 'No'],
            ],
            [
                'id' => self::F_Q4,
                'type' => 'radio',
                'label' => '4. Do you lose your balance because of dizziness, or do you ever lose consciousness?',
                'required' => true,
                'options' => ['Yes', 'No'],
            ],
            [
                'id' => self::F_Q5,
                'type' => 'radio',
                'label' => '5. Do you have a bone or joint problem (for example, back, knee or hip) that could be made worse by a change in your physical activity?',
                'required' => true,
                'options' => ['Yes', 'No'],
            ],
            [
                'id' => self::F_Q6,
                'type' => 'radio',
                'label' => '6. Is your doctor currently prescribing drugs (for example, water pills) for your blood pressure or heart condition?',
                'required' => true,
                'options' => ['Yes', 'No'],
            ],
            [
                'id' => self::F_Q7,
                'type' => 'radio',
                'label' => '7. Do you know of ANY OTHER REASON why you should not do physical activity?',
                'required' => true,
                'options' => ['Yes', 'No'],
            ],
            [
                'id' => self::F_PARA_NOTES,
                'type' => 'paragraph',
                'label' => "If you answered YES to one or more questions:\nTalk to your doctor by phone or in person BEFORE you start becoming much more physically active or BEFORE you have a fitness appraisal. Tell your doctor about the PAR-Q and which questions you answered YES."
                    . "\n\n"
                    . "If you answered NO to all questions:\nYou can be reasonably sure that you can start becoming much more physically active — begin slowly and build up gradually. This is the safest and easiest way to go."
                    . "\n\n"
                    . 'NOTE: This physical activity clearance is valid for a maximum of 12 months from the date it is completed and becomes invalid if your condition changes so that you would answer YES to any of the seven questions.',
                'required' => false,
            ],
            [
                'id' => self::F_PARA_DECL,
                'type' => 'paragraph',
                'label' => '"I have read, understood and completed this questionnaire. Any questions I had were answered to my full satisfaction."',
                'required' => false,
            ],
            [
                'id' => self::F_SIGNATURE,
                'type' => 'signature',
                'label' => 'Member Signature',
                'required' => true,
            ],
            [
                'id' => self::F_DATE,
                'type' => 'date',
                'label' => 'Date',
                'placeholder' => '',
                'required' => true,
            ],
        ];

        $translations = [

            // ── Sinhala ────────────────────────────────────────────────────────
            'si' => [
                'title' => 'ශාරීරික ක්‍රියාකාරකම් සූදානම් ප්‍රශ්නාවලිය (PAR-Q)',
                'description' => 'වයස 15 සිට 69 දක්වා පිරිස් සඳහා ව්‍යායාමයට පෙර පරීක්ෂා ප්‍රශ්නාවලිය. ව්‍යායාම වැඩසටහනක් ආරම්භ කිරීමට පෙර වෛද්‍ය අනුමැතිය අවශ්‍ය විය හැකි පුද්ගලයන් හඳුනා ගනී.',
                'fields' => [
                    self::F_HEADING => ['label' => 'ශාරීරික ක්‍රියාකාරකම් සූදානම් ප්‍රශ්නාවලිය (PAR-Q)'],
                    self::F_PARA_AGE => ['label' => 'වයස් 15 සිට 69 දක්වා පිරිස් සඳහා ප්‍රශ්නාවලිය'],
                    self::F_PARA_INTRO => ['label' => 'නිතිපතා ශාරීරික ව්‍යායාම ප්‍රීතිමත් හා සෞඛ්‍ය සම්පන්නය. බොහෝ දෙනෙකුට ශාරීරිකව ක්‍රියාශීලී වීම ඉතා ආරක්ෂිතය. කෙසේ නමුත් සමහරෙකු ශාරීරික ක්‍රියාකාරකම් ආරම්භ කිරීමට පෙර වෛද්‍යවරයෙකු හමු විය යුතුය.'
                        . "\n\n"
                        . 'ඔබ දැනට ශාරීරිකව ක්‍රියාශීලී වනවාට වඩා බොහෝ සෙයින් ක්‍රියාශීලී වීමට සැලසුම් කරනවා නම්, පහත ප්‍රශ්න හතකට පිළිතුරු දෙන්න. ඔබේ වයස 15 සිට 69 අතර නම් PAR-Q ඔබ ව්‍යායාම ආරම්භ කිරීමට පෙර වෛද්‍යවරයෙකු හමු විය යුතු දැයි ඔබට දනවයි. ඔබේ වයස 69 ට වඩා වැඩි නම් හා ශාරීරික ව්‍යායාමවලට හුරු නොවූ නම් ඔබේ වෛද්‍යවරයා හමු වන්න.'
                        . "\n\n"
                        . 'ප්‍රශ්නවලට පිළිතුරු දීමේදී ඔබේ සාමාන්‍ය බුද්ධිය හොඳම මාර්ගෝපදේශකයයි. ප්‍රශ්න හොඳින් කියවා, සෑම ප්‍රශ්නයකටම සෘජුව ඔව් හෝ නැත ලෙස පිළිතුරු දෙන්න.',
                    ],
                    self::F_Q1 => [
                        'label' => '1. ඔබට හෘද රෝගයක් ඇති බවත් ශාරීරික ක්‍රියාකාරකම් වෛද්‍යවරයෙකු නිර්දේශ කළ ඒවා පමණක් කළ යුතු බවත් ඔබේ වෛද්‍යවරයා කිසියම් දිනෙක ප්‍රකාශ කළේ ද?',
                        'options' => ['ඔව්', 'නැත'],
                    ],
                    self::F_Q2 => [
                        'label' => '2. ශාරීරික ක්‍රියාකාරකම් කිරීමේදී ඔබේ පපුවේ වේදනාවක් දැනෙනවාද?',
                        'options' => ['ඔව්', 'නැත'],
                    ],
                    self::F_Q3 => [
                        'label' => '3. පසුගිය මාසය තුළ ශාරීරික ක්‍රියාකාරකම් නොකර සිටිනවිට පපු වේදනාවක් ඇති වූ ද?',
                        'options' => ['ඔව්', 'නැත'],
                    ],
                    self::F_Q4 => [
                        'label' => '4. කරකැවිල්ල නිසා ඔබේ සමතුලිතතාවය නැතිවෙනවාද, නැතිනම් විඥනය නැතිවෙනවාද?',
                        'options' => ['ඔව්', 'නැත'],
                    ],
                    self::F_Q5 => [
                        'label' => '5. ශාරීරික ක්‍රියාකාරකමේ වෙනසකින් නරක් විය හැකි ඇට හෝ සන්ධි ගැටළුවක් (නිදසුනක් - පිට, දණ හෝ ඉණ) ඔබට ඇද්ද?',
                        'options' => ['ඔව්', 'නැත'],
                    ],
                    self::F_Q6 => [
                        'label' => '6. ඔබේ රුධිර පීඩනය හෝ හෘද රෝගය සඳහා ඔබේ වෛද්‍යවරයා දැනට ඖෂධ (නිදසුනක් - ජල කොළ) නිර්දේශ කරනවාද?',
                        'options' => ['ඔව්', 'නැත'],
                    ],
                    self::F_Q7 => [
                        'label' => '7. ශාරීරික ක්‍රියාකාරකම් නොකළ යුතු වෙනත් හේතුවක් ඔබ දන්නවාද?',
                        'options' => ['ඔව්', 'නැත'],
                    ],
                    self::F_PARA_NOTES => ['label' => "ප්‍රශ්නයකට ඔව් යැයි පිළිතුරු ලබා දුන්නේ නම්:\nශාරීරිකව ක්‍රියාශීලී ජීවිතයක් ආරම්භ කිරීමට පෙර හෝ යෝග්‍යතා ඇගැයීමකට පෙර ඔබේ වෛද්‍යවරයා දුරකතනයෙන් හෝ පෞද්ගලිකව හමු වන්න. PAR-Q ගැනත් ඔව් ලෙස පිළිතුරු දුන් ප්‍රශ්නත් ගැන ඔහුට/ඇයට දන්වන්න."
                        . "\n\n"
                        . "සියලු ප්‍රශ්නවලට නැත ලෙස පිළිතුරු ලබා දුන්නේ නම්:\nඔබට ශාරීරිකව ක්‍රියාශීලී ජීවිතයක් ආරම්භ කළ හැකිය — සෙමෙන් ආරම්භ කර ක්‍රමානුකූලව ඉහළ නංවන්න. ආරක්ෂිතම හා පහසුම ක්‍රමය ද එය."
                        . "\n\n"
                        . 'සටහන: මෙම ශාරීරික ක්‍රියාකාරකම් සඳහා අවසරය සම්පූර්ණ කළ දිනයේ සිට උපරිම මාස 12 ක් ක්‍රියාත්මක වේ. ඔබේ ශාරීරික තත්ත්වය ප්‍රශ්නයන් හතෙන් ඕනෑම එකකට ඔව් ලෙස පිළිතුරු දිය හැකි ලෙස වෙනස් වුවහොත් අවලංගු වේ.',
                    ],
                    self::F_PARA_DECL => ['label' => '"මෙම ප්‍රශ්නාවලිය කියවා, තේරුම් ගෙන, සම්පූර්ණ කළෙමි. මා ලබා ගත් ඕනෑම ප්‍රශ්නයකට මා සම්පූර්ණ සෑහීමකට ලක්වන පිළිතුරු ලබා දෙන ලදී."'],
                    self::F_SIGNATURE => ['label' => 'සාමාජිකයාගේ අත්සන'],
                    self::F_DATE => ['label' => 'දිනය'],
                ],
            ],

            // ── Tamil ──────────────────────────────────────────────────────────
            'ta' => [
                'title' => 'உடல் செயல்பாடு தயார்நிலை கேள்வித்தாள் (PAR-Q)',
                'description' => '15 முதல் 69 வயதுடையவர்களுக்கான உடற்பயிற்சிக்கு முந்தைய பரிசோதனை கேள்வித்தாள். உடற்பயிற்சி திட்டம் தொடங்கும் முன் மருத்துவ அனுமதி தேவைப்படுவோரை அடையாளம் காண உதவுகிறது.',
                'fields' => [
                    self::F_HEADING => ['label' => 'உடல் செயல்பாடு தயார்நிலை கேள்வித்தாள் (PAR-Q)'],
                    self::F_PARA_AGE => ['label' => '15 முதல் 69 வயதுடையவர்களுக்கான கேள்வித்தாள்'],
                    self::F_PARA_INTRO => ['label' => 'தொடர்ந்து உடல் செயல்பாடில் ஈடுபடுவது மகிழ்ச்சியானதும் ஆரோக்கியமானதும் ஆகும். பெரும்பாலானவர்களுக்கு அதிக உடல் செயல்பாடு மிகவும் பாதுகாப்பானது. இருப்பினும், சிலர் உடல் ரீதியாக மிகவும் சுறுசுறுப்பாக மாறுவதற்கு முன்பு மருத்துவரிடம் சோதிக்க வேண்டும்.'
                        . "\n\n"
                        . 'நீங்கள் இப்போது இருப்பதைவிட உடல் ரீதியாக மிகவும் சுறுசுறுப்பாக இருக்க திட்டமிட்டால், கீழுள்ள ஏழு கேள்விகளுக்கு பதிலளிக்கவும். உங்கள் வயது 15 முதல் 69 வரை இருந்தால், PAR-Q நீங்கள் தொடங்குவதற்கு முன் மருத்துவரிடம் சோதிக்க வேண்டுமா என்று கூறும். உங்கள் வயது 69 ஐ தாண்டியிருந்தால், மருத்துவரிடம் ஆலோசிக்கவும்.'
                        . "\n\n"
                        . 'இந்தக் கேள்விகளுக்கு பதிலளிக்கும்போது பொதுவான அறிவே உங்கள் சிறந்த வழிகாட்டி. ஒவ்வொரு கேள்வியையும் கவனமாகப் படித்து, ஆம் அல்லது இல்லை என்று நேர்மையாக பதிலளிக்கவும்.',
                    ],
                    self::F_Q1 => [
                        'label' => '1. உங்களுக்கு இதய நோய் இருப்பதாகவும் மருத்துவர் பரிந்துரைத்த உடல் செயல்பாடுகளை மட்டுமே செய்ய வேண்டும் என்றும் உங்கள் மருத்துவர் எப்போதாவது கூறியுள்ளாரா?',
                        'options' => ['ஆம்', 'இல்லை'],
                    ],
                    self::F_Q2 => [
                        'label' => '2. உடல் செயல்பாடு செய்யும்போது உங்கள் மார்பில் வலி உணர்கிறீர்களா?',
                        'options' => ['ஆம்', 'இல்லை'],
                    ],
                    self::F_Q3 => [
                        'label' => '3. கடந்த மாதத்தில் உடல் செயல்பாடு செய்யாதபோது மார்பு வலி ஏற்பட்டதுண்டா?',
                        'options' => ['ஆம்', 'இல்லை'],
                    ],
                    self::F_Q4 => [
                        'label' => '4. தலைசுற்றல் காரணமாக சமநிலையை இழக்கிறீர்களா, அல்லது எப்போதாவது சுயநினைவை இழந்ததுண்டா?',
                        'options' => ['ஆம்', 'இல்லை'],
                    ],
                    self::F_Q5 => [
                        'label' => '5. உடல் செயல்பாடு மாற்றத்தால் மோசமடையக்கூடிய எலும்பு அல்லது மூட்டுப் பிரச்சினை (உதாரணமாக, முதுகு, முழங்கால் அல்லது இடுப்பு) உங்களுக்கு உள்ளதா?',
                        'options' => ['ஆம்', 'இல்லை'],
                    ],
                    self::F_Q6 => [
                        'label' => '6. உயர் இரத்த அழுத்தம் அல்லது இதய நோய்க்காக உங்கள் மருத்துவர் தற்போது மருந்துகள் (உதாரணமாக, நீர் மாத்திரைகள்) பரிந்துரைக்கிறாரா?',
                        'options' => ['ஆம்', 'இல்லை'],
                    ],
                    self::F_Q7 => [
                        'label' => '7. உடல் செயல்பாடு செய்யக்கூடாத வேறு ஏதாவது காரணம் உங்களுக்குத் தெரியுமா?',
                        'options' => ['ஆம்', 'இல்லை'],
                    ],
                    self::F_PARA_NOTES => ['label' => "ஒன்று அல்லது அதிகமான கேள்விகளுக்கு ஆம் என்று பதிலளித்திருந்தால்:\nமிகவும் உடல் ரீதியாக சுறுசுறுப்பாக மாறுவதற்கு முன்பு அல்லது உடல் தகுதி மதிப்பீட்டிற்கு முன்பு மருத்துவரிடம் ஆலோசிக்கவும். PAR-Q பற்றியும் நீங்கள் ஆம் என்று பதிலளித்த கேள்விகளைப் பற்றியும் மருத்துவரிடம் கூறவும்."
                        . "\n\n"
                        . "அனைத்து கேள்விகளுக்கும் இல்லை என்று பதிலளித்திருந்தால்:\nநீங்கள் உடல் ரீதியாக மிகவும் சுறுசுறுப்பாக மாறத் தொடங்கலாம் — மெதுவாகத் தொடங்கி படிப்படியாக அதிகரிக்கவும். இதுவே மிகவும் பாதுகாப்பான வழி."
                        . "\n\n"
                        . 'குறிப்பு: இந்த உடல் செயல்பாட்டு அனுமதி நிரப்பப்பட்ட தேதியிலிருந்து அதிகபட்சம் 12 மாதங்களுக்கு செல்லுபடியாகும்.',
                    ],
                    self::F_PARA_DECL => ['label' => '"இந்தக் கேள்வித்தாளை படித்து, புரிந்துகொண்டு, பூர்த்தி செய்தேன். என் கேள்விகளுக்கு எனக்கு முழு திருப்தியளிக்கும் விதத்தில் பதில் அளிக்கப்பட்டது."'],
                    self::F_SIGNATURE => ['label' => 'உறுப்பினர் கையொப்பம்'],
                    self::F_DATE => ['label' => 'தேதி'],
                ],
            ],

            // ── French ─────────────────────────────────────────────────────────
            'fr' => [
                'title' => 'Questionnaire sur l\'aptitude à l\'activité physique (Q-AAP)',
                'description' => 'Questionnaire de présélection avant exercice pour les personnes de 15 à 69 ans. Identifie les personnes pouvant nécessiter un avis médical avant de commencer un programme d\'activité physique.',
                'fields' => [
                    self::F_HEADING => ['label' => 'Questionnaire sur l\'aptitude à l\'activité physique (Q-AAP)'],
                    self::F_PARA_AGE => ['label' => 'Un questionnaire pour les personnes de 15 à 69 ans'],
                    self::F_PARA_INTRO => ['label' => 'L\'activité physique régulière est agréable et saine, et de plus en plus de personnes commencent à devenir plus actives chaque jour. Être plus actif est très sûr pour la plupart des gens. Cependant, certaines personnes devraient consulter leur médecin avant de commencer à être beaucoup plus actives physiquement.'
                        . "\n\n"
                        . 'Si vous prévoyez de devenir beaucoup plus actif physiquement, commencez par répondre aux sept questions ci-dessous. Si vous avez entre 15 et 69 ans, le Q-AAP vous indiquera si vous devriez consulter votre médecin avant de commencer. Si vous avez plus de 69 ans et n\'êtes pas habitué à une activité physique intense, consultez votre médecin.'
                        . "\n\n"
                        . 'Le bon sens est votre meilleur guide pour répondre à ces questions. Veuillez lire attentivement les questions et répondre honnêtement à chacune : répondez OUI ou NON.',
                    ],
                    self::F_Q1 => [
                        'label' => '1. Votre médecin vous a-t-il déjà dit que vous souffrez d\'une maladie cardiaque ET que vous ne devez faire de l\'activité physique que sur recommandation médicale?',
                        'options' => ['Oui', 'Non'],
                    ],
                    self::F_Q2 => [
                        'label' => '2. Ressentez-vous des douleurs à la poitrine lorsque vous faites de l\'activité physique?',
                        'options' => ['Oui', 'Non'],
                    ],
                    self::F_Q3 => [
                        'label' => '3. Au cours du dernier mois, avez-vous eu des douleurs à la poitrine sans faire d\'activité physique?',
                        'options' => ['Oui', 'Non'],
                    ],
                    self::F_Q4 => [
                        'label' => '4. Perdez-vous votre équilibre à cause d\'étourdissements ou perdez-vous parfois connaissance?',
                        'options' => ['Oui', 'Non'],
                    ],
                    self::F_Q5 => [
                        'label' => '5. Avez-vous un problème osseux ou articulaire (par exemple, dos, genou ou hanche) qui pourrait être aggravé par une modification de votre activité physique?',
                        'options' => ['Oui', 'Non'],
                    ],
                    self::F_Q6 => [
                        'label' => '6. Des médicaments vous sont-ils actuellement prescrits par votre médecin (par exemple, des diurétiques) pour votre pression artérielle ou votre cœur?',
                        'options' => ['Oui', 'Non'],
                    ],
                    self::F_Q7 => [
                        'label' => '7. Connaissez-vous UNE AUTRE RAISON pour laquelle vous ne devriez pas faire de l\'activité physique?',
                        'options' => ['Oui', 'Non'],
                    ],
                    self::F_PARA_NOTES => ['label' => "Si vous avez répondu OUI à une ou plusieurs questions:\nConsultez votre médecin par téléphone ou en personne AVANT de commencer à être beaucoup plus actif physiquement ou AVANT de passer une évaluation de votre condition physique."
                        . "\n\n"
                        . "Si vous avez répondu NON à toutes les questions:\nVous pouvez raisonnablement commencer à être beaucoup plus actif physiquement — commencez lentement et augmentez progressivement. C'est la façon la plus sûre et la plus facile."
                        . "\n\n"
                        . 'REMARQUE: Cette autorisation d\'activité physique est valable pour un maximum de 12 mois à compter de la date d\'achèvement.',
                    ],
                    self::F_PARA_DECL => ['label' => '"J\'ai lu, compris et rempli ce questionnaire. Toutes les questions que j\'avais ont reçu des réponses à ma pleine satisfaction."'],
                    self::F_SIGNATURE => ['label' => 'Signature du membre'],
                    self::F_DATE => ['label' => 'Date'],
                ],
            ],

            // ── German ─────────────────────────────────────────────────────────
            'de' => [
                'title' => 'Fragebogen zur körperlichen Aktivitätsbereitschaft (PAR-Q)',
                'description' => 'Ein Vorsorge-Screening-Fragebogen für Personen zwischen 15 und 69 Jahren. Identifiziert Personen, die vor Beginn eines Fitnessprogramms möglicherweise eine ärztliche Freigabe benötigen.',
                'fields' => [
                    self::F_HEADING => ['label' => 'Fragebogen zur körperlichen Aktivitätsbereitschaft (PAR-Q)'],
                    self::F_PARA_AGE => ['label' => 'Ein Fragebogen für Personen zwischen 15 und 69 Jahren'],
                    self::F_PARA_INTRO => ['label' => 'Regelmäßige körperliche Aktivität macht Spaß und ist gesund. Für die meisten Menschen ist mehr körperliche Aktivität sehr sicher. Einige Personen sollten jedoch vor Beginn einer intensiveren körperlichen Aktivität ihren Arzt konsultieren.'
                        . "\n\n"
                        . 'Wenn Sie planen, deutlich aktiver zu werden, beantworten Sie zunächst die sieben Fragen unten. Wenn Sie zwischen 15 und 69 Jahre alt sind, teilt Ihnen der PAR-Q mit, ob Sie vor dem Start einen Arzt aufsuchen sollten. Wenn Sie über 69 Jahre alt sind und nicht an intensive körperliche Aktivität gewöhnt sind, wenden Sie sich an Ihren Arzt.'
                        . "\n\n"
                        . 'Der gesunde Menschenverstand ist Ihr bester Ratgeber beim Beantworten dieser Fragen. Lesen Sie jede Frage sorgfältig durch und antworten Sie ehrlich: Ja oder Nein.',
                    ],
                    self::F_Q1 => [
                        'label' => '1. Hat Ihr Arzt jemals gesagt, dass Sie an einer Herzerkrankung leiden UND dass Sie körperliche Aktivität nur auf ärztliche Empfehlung ausüben sollten?',
                        'options' => ['Ja', 'Nein'],
                    ],
                    self::F_Q2 => [
                        'label' => '2. Haben Sie Schmerzen in der Brust, wenn Sie körperlich aktiv sind?',
                        'options' => ['Ja', 'Nein'],
                    ],
                    self::F_Q3 => [
                        'label' => '3. Hatten Sie im letzten Monat Brustschmerzen, ohne körperlich aktiv zu sein?',
                        'options' => ['Ja', 'Nein'],
                    ],
                    self::F_Q4 => [
                        'label' => '4. Verlieren Sie aufgrund von Schwindel das Gleichgewicht, oder verlieren Sie manchmal das Bewusstsein?',
                        'options' => ['Ja', 'Nein'],
                    ],
                    self::F_Q5 => [
                        'label' => '5. Haben Sie ein Knochen- oder Gelenkproblem (z. B. Rücken, Knie oder Hüfte), das sich durch eine Änderung Ihrer körperlichen Aktivität verschlimmern könnte?',
                        'options' => ['Ja', 'Nein'],
                    ],
                    self::F_Q6 => [
                        'label' => '6. Werden Ihnen derzeit von Ihrem Arzt Medikamente (z. B. Wassertabletten) für Ihren Blutdruck oder Ihre Herzerkrankung verschrieben?',
                        'options' => ['Ja', 'Nein'],
                    ],
                    self::F_Q7 => [
                        'label' => '7. Kennen Sie EINEN ANDEREN GRUND, warum Sie keine körperliche Aktivität ausüben sollten?',
                        'options' => ['Ja', 'Nein'],
                    ],
                    self::F_PARA_NOTES => ['label' => "Wenn Sie eine oder mehrere Fragen mit Ja beantwortet haben:\nSprechen Sie mit Ihrem Arzt per Telefon oder persönlich, BEVOR Sie mit deutlich mehr körperlicher Aktivität beginnen oder BEVOR Sie eine Fitnessbeurteilung durchführen lassen."
                        . "\n\n"
                        . "Wenn Sie alle Fragen mit Nein beantwortet haben:\nSie können davon ausgehen, dass Sie mit mehr körperlicher Aktivität beginnen können — fangen Sie langsam an und steigern Sie sich allmählich. Dies ist der sicherste und einfachste Weg."
                        . "\n\n"
                        . 'HINWEIS: Diese Freigabe für körperliche Aktivität gilt für maximal 12 Monate ab dem Datum der Fertigstellung.',
                    ],
                    self::F_PARA_DECL => ['label' => '"Ich habe diesen Fragebogen gelesen, verstanden und ausgefüllt. Alle Fragen, die ich hatte, wurden zu meiner vollen Zufriedenheit beantwortet."'],
                    self::F_SIGNATURE => ['label' => 'Unterschrift des Mitglieds'],
                    self::F_DATE => ['label' => 'Datum'],
                ],
            ],

            // ── Spanish ────────────────────────────────────────────────────────
            'es' => [
                'title' => 'Cuestionario de Aptitud para la Actividad Física (PAR-Q)',
                'description' => 'Cuestionario de selección previa al ejercicio para personas de 15 a 69 años. Identifica a quienes pueden necesitar autorización médica antes de comenzar un programa de fitness.',
                'fields' => [
                    self::F_HEADING => ['label' => 'Cuestionario de Aptitud para la Actividad Física (PAR-Q)'],
                    self::F_PARA_AGE => ['label' => 'Un cuestionario para personas de 15 a 69 años'],
                    self::F_PARA_INTRO => ['label' => 'La actividad física regular es divertida y saludable, y cada vez más personas comienzan a ser más activas cada día. Ser más activo es muy seguro para la mayoría de las personas. Sin embargo, algunas personas deben consultar a su médico antes de comenzar a ser mucho más activas físicamente.'
                        . "\n\n"
                        . 'Si planea volverse mucho más activo físicamente, comience respondiendo las siete preguntas a continuación. Si tiene entre 15 y 69 años, el PAR-Q le dirá si debe consultar a su médico antes de comenzar. Si tiene más de 69 años y no está acostumbrado a ser muy activo, consulte a su médico.'
                        . "\n\n"
                        . 'El sentido común es su mejor guía al responder estas preguntas. Lea las preguntas con cuidado y responda cada una honestamente: responda SÍ o NO.',
                    ],
                    self::F_Q1 => [
                        'label' => '1. ¿Le ha dicho alguna vez su médico que tiene una afección cardíaca Y que solo debe realizar actividad física recomendada por un médico?',
                        'options' => ['Sí', 'No'],
                    ],
                    self::F_Q2 => [
                        'label' => '2. ¿Siente dolor en el pecho cuando hace actividad física?',
                        'options' => ['Sí', 'No'],
                    ],
                    self::F_Q3 => [
                        'label' => '3. En el último mes, ¿ha tenido dolor en el pecho sin estar haciendo actividad física?',
                        'options' => ['Sí', 'No'],
                    ],
                    self::F_Q4 => [
                        'label' => '4. ¿Pierde el equilibrio debido a mareos o alguna vez pierde el conocimiento?',
                        'options' => ['Sí', 'No'],
                    ],
                    self::F_Q5 => [
                        'label' => '5. ¿Tiene algún problema óseo o articular (por ejemplo, espalda, rodilla o cadera) que podría empeorar con un cambio en su actividad física?',
                        'options' => ['Sí', 'No'],
                    ],
                    self::F_Q6 => [
                        'label' => '6. ¿Le está recetando actualmente su médico medicamentos (por ejemplo, píldoras de agua) para su presión arterial o afección cardíaca?',
                        'options' => ['Sí', 'No'],
                    ],
                    self::F_Q7 => [
                        'label' => '7. ¿Conoce ALGUNA OTRA RAZÓN por la que no debería hacer actividad física?',
                        'options' => ['Sí', 'No'],
                    ],
                    self::F_PARA_NOTES => ['label' => "Si respondió SÍ a una o más preguntas:\nHable con su médico por teléfono o en persona ANTES de comenzar a ser mucho más activo físicamente o ANTES de hacerse una evaluación de aptitud física."
                        . "\n\n"
                        . "Si respondió NO a todas las preguntas:\nPuede estar razonablemente seguro de que puede comenzar a ser mucho más activo físicamente — comience lentamente y aumente gradualmente. Esta es la forma más segura y fácil."
                        . "\n\n"
                        . 'NOTA: Esta autorización de actividad física es válida por un máximo de 12 meses desde la fecha en que se completa.',
                    ],
                    self::F_PARA_DECL => ['label' => '"He leído, comprendido y completado este cuestionario. Las preguntas que tenía fueron respondidas a mi completa satisfacción."'],
                    self::F_SIGNATURE => ['label' => 'Firma del miembro'],
                    self::F_DATE => ['label' => 'Fecha'],
                ],
            ],

            // ── Portuguese ─────────────────────────────────────────────────────
            'pt' => [
                'title' => 'Questionário de Prontidão para Atividade Física (PAR-Q)',
                'description' => 'Questionário de triagem pré-exercício para pessoas entre 15 e 69 anos. Identifica indivíduos que podem necessitar de autorização médica antes de iniciar um programa de fitness.',
                'fields' => [
                    self::F_HEADING => ['label' => 'Questionário de Prontidão para Atividade Física (PAR-Q)'],
                    self::F_PARA_AGE => ['label' => 'Um questionário para pessoas entre 15 e 69 anos'],
                    self::F_PARA_INTRO => ['label' => 'A atividade física regular é divertida e saudável, e cada vez mais pessoas estão se tornando mais ativas. Ser mais ativo é muito seguro para a maioria das pessoas. No entanto, algumas pessoas devem consultar seu médico antes de se tornarem muito mais ativas fisicamente.'
                        . "\n\n"
                        . 'Se você planeja se tornar muito mais ativo fisicamente, comece respondendo às sete perguntas abaixo. Se você tem entre 15 e 69 anos, o PAR-Q dirá se você deve consultar seu médico antes de começar. Se você tem mais de 69 anos e não está acostumado a ser muito ativo, consulte seu médico.'
                        . "\n\n"
                        . 'O bom senso é seu melhor guia ao responder estas perguntas. Leia as perguntas com atenção e responda cada uma honestamente: responda SIM ou NÃO.',
                    ],
                    self::F_Q1 => [
                        'label' => '1. Seu médico já disse que você tem uma condição cardíaca E que você só deve fazer atividade física recomendada por um médico?',
                        'options' => ['Sim', 'Não'],
                    ],
                    self::F_Q2 => [
                        'label' => '2. Você sente dor no peito quando faz atividade física?',
                        'options' => ['Sim', 'Não'],
                    ],
                    self::F_Q3 => [
                        'label' => '3. No último mês, você teve dor no peito sem estar fazendo atividade física?',
                        'options' => ['Sim', 'Não'],
                    ],
                    self::F_Q4 => [
                        'label' => '4. Você perde o equilíbrio por causa de tontura ou alguma vez perde a consciência?',
                        'options' => ['Sim', 'Não'],
                    ],
                    self::F_Q5 => [
                        'label' => '5. Você tem algum problema ósseo ou articular (por exemplo, costas, joelho ou quadril) que poderia piorar com uma mudança na sua atividade física?',
                        'options' => ['Sim', 'Não'],
                    ],
                    self::F_Q6 => [
                        'label' => '6. Seu médico está prescrevendo medicamentos (por exemplo, diuréticos) para sua pressão arterial ou condição cardíaca?',
                        'options' => ['Sim', 'Não'],
                    ],
                    self::F_Q7 => [
                        'label' => '7. Você conhece ALGUMA OUTRA RAZÃO pela qual não deveria fazer atividade física?',
                        'options' => ['Sim', 'Não'],
                    ],
                    self::F_PARA_NOTES => ['label' => "Se respondeu SIM a uma ou mais perguntas:\nFale com seu médico por telefone ou pessoalmente ANTES de começar a ser muito mais ativo fisicamente ou ANTES de fazer uma avaliação de aptidão física."
                        . "\n\n"
                        . "Se respondeu NÃO a todas as perguntas:\nVocê pode ter razoável certeza de que pode começar a ser muito mais ativo fisicamente — comece devagar e aumente gradualmente. Esta é a forma mais segura e fácil."
                        . "\n\n"
                        . 'NOTA: Esta autorização de atividade física é válida por no máximo 12 meses a partir da data de conclusão.',
                    ],
                    self::F_PARA_DECL => ['label' => '"Li, compreendi e completei este questionário. As perguntas que tinha foram respondidas à minha total satisfação."'],
                    self::F_SIGNATURE => ['label' => 'Assinatura do membro'],
                    self::F_DATE => ['label' => 'Data'],
                ],
            ],

            // ── Chinese ────────────────────────────────────────────────────────
            'zh' => [
                'title' => '体力活动准备问卷 (PAR-Q)',
                'description' => '针对15至69岁人群的运动前筛查问卷。用于识别在开始健身计划之前可能需要获得医疗许可的人员。',
                'fields' => [
                    self::F_HEADING => ['label' => '体力活动准备问卷 (PAR-Q)'],
                    self::F_PARA_AGE => ['label' => '适用于15至69岁人群的问卷'],
                    self::F_PARA_INTRO => ['label' => '定期进行体育锻炼既有趣又健康，越来越多的人每天都在变得更加活跃。对大多数人来说，更加活跃非常安全。但是，有些人在开始大量增加体育活动之前应该咨询医生。'
                        . "\n\n"
                        . '如果您计划比现在更加积极地进行体育活动，请先回答以下七个问题。如果您年龄在15至69岁之间，PAR-Q将告诉您在开始之前是否应该咨询医生。如果您年龄超过69岁且不习惯非常活跃，请咨询您的医生。'
                        . "\n\n"
                        . '回答这些问题时，常识是您最好的指南。请仔细阅读问题，并如实回答每一个问题：回答"是"或"否"。',
                    ],
                    self::F_Q1 => [
                        'label' => '1. 您的医生是否曾经告诉您，您患有心脏病，并且您只应该进行医生推荐的体育活动？',
                        'options' => ['是', '否'],
                    ],
                    self::F_Q2 => [
                        'label' => '2. 当您进行体育活动时，是否感到胸痛？',
                        'options' => ['是', '否'],
                    ],
                    self::F_Q3 => [
                        'label' => '3. 在过去一个月中，当您不进行体育活动时，是否有胸痛？',
                        'options' => ['是', '否'],
                    ],
                    self::F_Q4 => [
                        'label' => '4. 您是否因头晕而失去平衡，或是否曾经失去意识？',
                        'options' => ['是', '否'],
                    ],
                    self::F_Q5 => [
                        'label' => '5. 您是否有骨骼或关节问题（例如，背部、膝盖或髋部），可能因体育活动的改变而加重？',
                        'options' => ['是', '否'],
                    ],
                    self::F_Q6 => [
                        'label' => '6. 您的医生目前是否为您开具药物（例如，利尿剂）来控制血压或心脏病？',
                        'options' => ['是', '否'],
                    ],
                    self::F_Q7 => [
                        'label' => '7. 您是否知道任何其他不应该进行体育活动的原因？',
                        'options' => ['是', '否'],
                    ],
                    self::F_PARA_NOTES => ['label' => "如果您对一个或多个问题回答了\"是\"：\n在开始更加活跃的体育活动之前，或在进行健康评估之前，请亲自或电话咨询您的医生。请告诉医生PAR-Q以及您回答\"是\"的问题。"
                        . "\n\n"
                        . "如果您对所有问题都回答了\"否\"：\n您可以合理地确信自己可以开始变得更加活跃——慢慢开始，逐步增加。这是最安全、最简单的方式。"
                        . "\n\n"
                        . '注意：此体育活动许可自完成之日起最长有效期为12个月，如果您的状况发生变化导致您会对七个问题中的任何一个回答"是"，则此许可将失效。',
                    ],
                    self::F_PARA_DECL => ['label' => '"我已阅读、理解并完成了本问卷。我的所有问题都得到了令我完全满意的回答。"'],
                    self::F_SIGNATURE => ['label' => '会员签名'],
                    self::F_DATE => ['label' => '日期'],
                ],
            ],

            // ── Japanese ───────────────────────────────────────────────────────
            'ja' => [
                'title' => '身体活動準備質問票 (PAR-Q)',
                'description' => '15歳から69歳の方を対象とした運動前スクリーニング質問票。フィットネスプログラムを開始する前に医療許可が必要な方を特定します。',
                'fields' => [
                    self::F_HEADING => ['label' => '身体活動準備質問票 (PAR-Q)'],
                    self::F_PARA_AGE => ['label' => '15歳から69歳の方のための質問票'],
                    self::F_PARA_INTRO => ['label' => '定期的な身体活動は楽しく健康的であり、毎日より多くの人々が活動的になり始めています。より活動的になることはほとんどの人にとって非常に安全です。しかし、身体活動を大幅に増やし始める前に医師に確認すべき人もいます。'
                        . "\n\n"
                        . '現在よりもはるかに身体的に活動的になる計画がある場合は、まず以下の7つの質問に答えてください。15歳から69歳の場合、PAR-Qを使用することで開始前に医師に相談すべきかどうかがわかります。69歳以上で非常に活動的でない場合は、医師に相談してください。'
                        . "\n\n"
                        . 'これらの質問に答える際は、常識が最善の指針です。質問をよく読み、正直に答えてください：はいまたはいいえで答えてください。',
                    ],
                    self::F_Q1 => [
                        'label' => '1. 心臓病があり、医師が推奨する身体活動のみを行うべきだと医師から言われたことがありますか？',
                        'options' => ['はい', 'いいえ'],
                    ],
                    self::F_Q2 => [
                        'label' => '2. 身体活動を行うときに胸の痛みを感じますか？',
                        'options' => ['はい', 'いいえ'],
                    ],
                    self::F_Q3 => [
                        'label' => '3. 過去1ヶ月間に、身体活動をしていないときに胸の痛みがありましたか？',
                        'options' => ['はい', 'いいえ'],
                    ],
                    self::F_Q4 => [
                        'label' => '4. めまいのためにバランスを失うことがありますか、または意識を失うことがありますか？',
                        'options' => ['はい', 'いいえ'],
                    ],
                    self::F_Q5 => [
                        'label' => '5. 身体活動の変化によって悪化する可能性のある骨や関節の問題（例：腰、膝、股関節）がありますか？',
                        'options' => ['はい', 'いいえ'],
                    ],
                    self::F_Q6 => [
                        'label' => '6. 血圧や心臓病のために医師から現在薬（例：利尿剤）を処方されていますか？',
                        'options' => ['はい', 'いいえ'],
                    ],
                    self::F_Q7 => [
                        'label' => '7. 身体活動を行うべきではない他の理由を知っていますか？',
                        'options' => ['はい', 'いいえ'],
                    ],
                    self::F_PARA_NOTES => ['label' => "1つ以上の質問に「はい」と答えた場合：\n身体活動を大幅に増やす前、またはフィットネス評価を受ける前に、電話または対面で医師に相談してください。PAR-Qについてと「はい」と答えた質問について医師に伝えてください。"
                        . "\n\n"
                        . "すべての質問に「いいえ」と答えた場合：\nより身体的に活動的になり始めることができると合理的に確信できます。ゆっくり始めて徐々に増やしてください。これが最も安全で簡単な方法です。"
                        . "\n\n"
                        . '注意：この身体活動許可は、完了日から最長12ヶ月間有効であり、7つの質問のいずれかに「はい」と答えるような状態変化があった場合は無効になります。',
                    ],
                    self::F_PARA_DECL => ['label' => '"この質問票を読み、理解し、記入しました。質問はすべて十分に満足のいく回答を得ました。"'],
                    self::F_SIGNATURE => ['label' => '会員署名'],
                    self::F_DATE => ['label' => '日付'],
                ],
            ],

            // ── Arabic ─────────────────────────────────────────────────────────
            'ar' => [
                'title' => 'استبيان الاستعداد للنشاط البدني (PAR-Q)',
                'description' => 'استبيان فحص ما قبل التمرين للأشخاص الذين تتراوح أعمارهم بين 15 و69 عامًا. يحدد الأفراد الذين قد يحتاجون إلى موافقة طبية قبل البدء في برنامج اللياقة البدنية.',
                'fields' => [
                    self::F_HEADING => ['label' => 'استبيان الاستعداد للنشاط البدني (PAR-Q)'],
                    self::F_PARA_AGE => ['label' => 'استبيان للأشخاص الذين تتراوح أعمارهم بين 15 و69 عامًا'],
                    self::F_PARA_INTRO => ['label' => 'النشاط البدني المنتظم ممتع وصحي، ويبدأ المزيد من الناس كل يوم في أن يصبحوا أكثر نشاطًا. يُعد النشاط البدني الأكبر آمنًا جدًا لمعظم الناس. ومع ذلك، يجب على بعض الأشخاص استشارة طبيبهم قبل البدء في ممارسة نشاط بدني أكثر بكثير.'
                        . "\n\n"
                        . 'إذا كنت تخطط لأن تصبح أكثر نشاطًا بدنيًا مما أنت عليه الآن، فابدأ بالإجابة على الأسئلة السبعة أدناه. إذا كان عمرك بين 15 و69 عامًا، فسيخبرك PAR-Q ما إذا كان يجب عليك استشارة طبيبك قبل البدء. إذا كان عمرك أكثر من 69 عامًا ولم تكن معتادًا على النشاط الشديد، فاستشر طبيبك.'
                        . "\n\n"
                        . 'يُعد الحس السليم دليلك الأفضل عند الإجابة على هذه الأسئلة. يرجى قراءة الأسئلة بعناية والإجابة على كل منها بصدق: أجب بنعم أو لا.',
                    ],
                    self::F_Q1 => [
                        'label' => '1. هل أخبرك طبيبك من قبل بأن لديك حالة قلبية وأنه يجب عليك ممارسة النشاط البدني الذي يوصي به الطبيب فقط؟',
                        'options' => ['نعم', 'لا'],
                    ],
                    self::F_Q2 => [
                        'label' => '2. هل تشعر بألم في صدرك عند ممارسة النشاط البدني؟',
                        'options' => ['نعم', 'لا'],
                    ],
                    self::F_Q3 => [
                        'label' => '3. في الشهر الماضي، هل عانيت من ألم في الصدر عندما لم تكن تمارس أي نشاط بدني؟',
                        'options' => ['نعم', 'لا'],
                    ],
                    self::F_Q4 => [
                        'label' => '4. هل تفقد توازنك بسبب الدوخة، أو هل فقدت الوعي في أي وقت؟',
                        'options' => ['نعم', 'لا'],
                    ],
                    self::F_Q5 => [
                        'label' => '5. هل لديك مشكلة في العظام أو المفاصل (مثل الظهر أو الركبة أو الورك) قد تتفاقم بسبب تغيير نشاطك البدني؟',
                        'options' => ['نعم', 'لا'],
                    ],
                    self::F_Q6 => [
                        'label' => '6. هل يصف لك طبيبك حاليًا أدوية (مثل حبوب الماء) لضغط الدم أو حالة القلب؟',
                        'options' => ['نعم', 'لا'],
                    ],
                    self::F_Q7 => [
                        'label' => '7. هل تعرف أي سبب آخر يجعلك لا تمارس النشاط البدني؟',
                        'options' => ['نعم', 'لا'],
                    ],
                    self::F_PARA_NOTES => ['label' => "إذا أجبت بنعم على سؤال واحد أو أكثر:\nتحدث إلى طبيبك عبر الهاتف أو شخصيًا قبل أن تبدأ في ممارسة المزيد من النشاط البدني أو قبل إجراء تقييم لياقة بدنية. أخبر طبيبك عن PAR-Q والأسئلة التي أجبت عليها بنعم."
                        . "\n\n"
                        . "إذا أجبت بلا على جميع الأسئلة:\nيمكنك أن تكون واثقًا بشكل معقول من أنك تستطيع البدء في أن تصبح أكثر نشاطًا بدنيًا — ابدأ ببطء وزد تدريجيًا. هذه هي الطريقة الأكثر أمانًا وسهولة."
                        . "\n\n"
                        . 'ملاحظة: يكون هذا الإذن بممارسة النشاط البدني صالحًا لمدة أقصاها 12 شهرًا من تاريخ الإكمال ويصبح غير صالح إذا تغيرت حالتك بحيث تجيب بنعم على أي من الأسئلة السبعة.',
                    ],
                    self::F_PARA_DECL => ['label' => '"لقد قرأت هذا الاستبيان وفهمته وأكملته. تمت الإجابة على جميع أسئلتي إلى رضاي التام."'],
                    self::F_SIGNATURE => ['label' => 'توقيع العضو'],
                    self::F_DATE => ['label' => 'التاريخ'],
                ],
            ],

        ];

        $data = [
            'title' => 'Physical Activity Readiness Questionnaire (PAR-Q)',
            'description' => 'A pre-exercise screening questionnaire for people aged 15 to 69. Identifies individuals who may need medical clearance before beginning a fitness program.',
            'is_active' => true,
            'fields' => $fields,
            'translations' => $translations,
        ];

        foreach (Tenant::all() as $tenant) {
            $existing = FormTemplate::where('title', $data['title'])
                ->first();

            if ($existing) {
                $existing->update([
                    'fields' => $formBuilder->normalizeFields($data['fields']),
                    'translations' => $formBuilder->normalizeTranslations($data['translations']),
                    'description' => $data['description'],
                    'is_active' => $data['is_active'],
                ]);
                $this->command->line("  Updated [{$tenant->name}] — PAR-Q fields & translations refreshed.");
                continue;
            }

            $formBuilder->storeTemplate($tenant->id, null, $data);
            $this->command->info("  Created PAR-Q template for [{$tenant->name}].");
        }
    }
}
