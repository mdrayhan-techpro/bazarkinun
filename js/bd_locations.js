/**
 * Bangladesh 64 Districts and Upazilas/Police Stations Dataset & Offline Shim
 * Guarantees 100% offline reliability without depending on third-party APIs (e.g. bdapis.com).
 */
(function() {
    const BD_LOCATIONS = {
    "Bagerhat": [
        "Bagerhat Sadar",
        "Chitalmari",
        "Fakirhat",
        "Kachua",
        "Mollahat",
        "Mongla",
        "Morrelganj",
        "Rampal",
        "Sarankhola"
    ],
    "Bandarban": [
        "Ali Kadam",
        "Bandarban Sadar",
        "Lama",
        "Naikhongchhari",
        "Rowangchhari",
        "Ruma",
        "Thanchi"
    ],
    "Barguna": [
        "Amtali",
        "Bamna",
        "Barguna Sadar",
        "Betagi",
        "Patharghata",
        "Taltali"
    ],
    "Barishal": [
        "Agailjhara",
        "Babuganj",
        "Bakerganj",
        "Banaripara",
        "Gaurnadi",
        "Hizla",
        "Barishal Sadar",
        "Mehendiganj",
        "Muladi",
        "Wazirpur"
    ],
    "Bhola": [
        "Bhola Sadar",
        "Burhanuddin",
        "Char Fasson",
        "Daulatkhan",
        "Lalmohan",
        "Manpura",
        "Tazumuddin"
    ],
    "Bogura": [
        "Adamdighi",
        "Bogura Sadar",
        "Dhunat",
        "Dhupchanchia",
        "Gabtali",
        "Kahaloo",
        "Nandigram",
        "Sariakandi",
        "Shajahanpur",
        "Sherpur",
        "Shibganj",
        "Sonatala"
    ],
    "Brahmanbaria": [
        "Akhaura",
        "Ashuganj",
        "Bancharampur",
        "Bijoynagar",
        "Brahmanbaria Sadar",
        "Kasba",
        "Nabinagar",
        "Nasirnagar",
        "Sarail"
    ],
    "Chandpur": [
        "Chandpur Sadar",
        "Faridganj",
        "Haimchar",
        "Hajiganj",
        "Kachua",
        "Matlab Dakshin",
        "Matlab Uttar",
        "Shahrasti"
    ],
    "Chapainawabganj": [
        "Bholahat",
        "Chapainawabganj Sadar",
        "Gomastapur",
        "Nachole",
        "Shibganj"
    ],
    "Chattogram": [
        "Anwara",
        "Banshkhali",
        "Boalkhali",
        "Chandanaish",
        "Fatikchhari",
        "Hathazari",
        "Karnaphuli",
        "Lohagara",
        "Mirsharai",
        "Patiya",
        "Rangunia",
        "Raozan",
        "Sandwip",
        "Satkania",
        "Sitakunda",
        "Bakalia",
        "Bandar",
        "Bayazid",
        "Chandgaon",
        "Double Mooring",
        "Halishahar",
        "Khulshi",
        "Kotwali",
        "Pahartali",
        "Panchlaish",
        "Patenga"
    ],
    "Chuadanga": [
        "Alamdanga",
        "Chuadanga Sadar",
        "Damurhuda",
        "Jibannagar"
    ],
    "Cox's Bazar": [
        "Chakaria",
        "Cox's Bazar Sadar",
        "Kutubdia",
        "Maheshkhali",
        "Pekua",
        "Ramu",
        "Teknaf",
        "Ukhia"
    ],
    "Cumilla": [
        "Barura",
        "Brahmanpara",
        "Burichang",
        "Chandina",
        "Chauddagram",
        "Cumilla Sadar",
        "Cumilla Sadar Dakshin",
        "Daudkandi",
        "Debidwar",
        "Homna",
        "Laksam",
        "Lalmai",
        "Meghna",
        "Monohargonj",
        "Muradnagar",
        "Nangalkot",
        "Titas"
    ],
    "Dhaka": [
        "Adabor",
        "Badda",
        "Banani",
        "Bangshal",
        "Bhashantek",
        "Bhatara",
        "Cantonment",
        "Chawkbazar",
        "Dakshinkhan",
        "Darus Salam",
        "Demra",
        "Dhamrai",
        "Dhanmondi",
        "Dohar",
        "Gendaria",
        "Gulshan",
        "Hazaribagh",
        "Jatrabari",
        "Kadamtali",
        "Kafrul",
        "Kalabagan",
        "Kamrangirchar",
        "Keraniganj",
        "Khilgaon",
        "Khilkhet",
        "Kotwali",
        "Lalbagh",
        "Mirpur",
        "Mohammadpur",
        "Motijheel",
        "Mugda",
        "Nawabganj",
        "New Market",
        "Pallabi",
        "Paltan",
        "Ramna",
        "Rampura",
        "Rupnagar",
        "Sabujbagh",
        "Savar",
        "Shah Ali",
        "Shahbagh",
        "Shahjahanpur",
        "Sher-e-Bangla Nagar",
        "Shyampur",
        "Sutrapur",
        "Tejgaon",
        "Tejgaon Industrial Area",
        "Turag",
        "Uttara East",
        "Uttara West",
        "Uttar Khan",
        "Wari"
    ],
    "Dinajpur": [
        "Birampur",
        "Birganj",
        "Biral",
        "Bochaganj",
        "Chirirbandar",
        "Dinajpur Sadar",
        "Fulbari",
        "Ghoraghat",
        "Hakimpur",
        "Kaharole",
        "Khansama",
        "Nawabganj",
        "Parbatipur"
    ],
    "Faridpur": [
        "Alfadanga",
        "Bhanga",
        "Boalmari",
        "Charbhadrasan",
        "Faridpur Sadar",
        "Madhukhali",
        "Nagarkanda",
        "Sadarpur",
        "Saltha"
    ],
    "Feni": [
        "Chhagalnaiya",
        "Daganbhuiyan",
        "Feni Sadar",
        "Fulgazi",
        "Parshuram",
        "Sonagazi"
    ],
    "Gaibandha": [
        "Fulchhari",
        "Gaibandha Sadar",
        "Gobindaganj",
        "Palashbari",
        "Sadullapur",
        "Saghata",
        "Sundarganj"
    ],
    "Gazipur": [
        "Gazipur Sadar",
        "Kaliakair",
        "Kaliganj",
        "Kapasia",
        "Sreepur",
        "Tongi"
    ],
    "Gopalganj": [
        "Gopalganj Sadar",
        "Kashiani",
        "Kotalipara",
        "Muksudpur",
        "Tungipara"
    ],
    "Habiganj": [
        "Ajmiriganj",
        "Bahubal",
        "Baniyachong",
        "Chunarughat",
        "Habiganj Sadar",
        "Lakhai",
        "Madhabpur",
        "Nabiganj",
        "Sayestaganj"
    ],
    "Jamalpur": [
        "Baksiganj",
        "Dewanganj",
        "Islampur",
        "Jamalpur Sadar",
        "Madarganj",
        "Melandaha",
        "Sarishabari"
    ],
    "Jashore": [
        "Abhaynagar",
        "Bagherpara",
        "Chaugachha",
        "Jhikargachha",
        "Keshabpur",
        "Jashore Sadar",
        "Manirampur",
        "Sharsha"
    ],
    "Jhalokathi": [
        "Jhalokathi Sadar",
        "Kanthalia",
        "Nalchity",
        "Rajapur"
    ],
    "Jhenaidah": [
        "Harinakunda",
        "Jhenaidah Sadar",
        "Kaliganj",
        "Kotchandpur",
        "Maheshpur",
        "Shailkupa"
    ],
    "Joypurhat": [
        "Akkelpur",
        "Joypurhat Sadar",
        "Kalai",
        "Khetlal",
        "Panchbibi"
    ],
    "Khagrachhari": [
        "Dighinala",
        "Guimara",
        "Khagrachhari Sadar",
        "Lakshmichhari",
        "Mahalchhari",
        "Manikchhari",
        "Matiranga",
        "Panchhari",
        "Ramgarh"
    ],
    "Khulna": [
        "Batiaghata",
        "Dacope",
        "Dighalia",
        "Dumuria",
        "Koyra",
        "Paikgachha",
        "Phultala",
        "Rupsha",
        "Terokhada",
        "Daulatpur",
        "Khalishpur",
        "Khan Jahan Ali",
        "Kotwali",
        "Sonadanga"
    ],
    "Kishoreganj": [
        "Astagram",
        "Bajitpur",
        "Bhairab",
        "Hossainpur",
        "Itna",
        "Karimganj",
        "Katiadi",
        "Kishoreganj Sadar",
        "Kuliarchar",
        "Mithamain",
        "Nikli",
        "Pakundia",
        "Tarail"
    ],
    "Kurigram": [
        "Bhurungamari",
        "Char Rajibpur",
        "Chilmari",
        "Kurigram Sadar",
        "Nageshwari",
        "Phulbari",
        "Rajarhat",
        "Roulmari",
        "Ulipur"
    ],
    "Kushtia": [
        "Bheramara",
        "Daulatpur",
        "Khoksa",
        "Kumarkhali",
        "Kushtia Sadar",
        "Mirpur"
    ],
    "Lakshmipur": [
        "Kamalnagar",
        "Lakshmipur Sadar",
        "Raipur",
        "Ramganj",
        "Ramgati"
    ],
    "Lalmonirhat": [
        "Aditmari",
        "Hatibandha",
        "Kaliganj",
        "Lalmonirhat Sadar",
        "Patgram"
    ],
    "Madaripur": [
        "Dasar",
        "Kalkini",
        "Madaripur Sadar",
        "Rajoir",
        "Shibchar"
    ],
    "Magura": [
        "Magura Sadar",
        "Mohammadpur",
        "Shalikha",
        "Sreepur"
    ],
    "Manikganj": [
        "Daulatpur",
        "Ghior",
        "Harirampur",
        "Manikganj Sadar",
        "Saturia",
        "Shivalaya",
        "Singair"
    ],
    "Meherpur": [
        "Gangni",
        "Meherpur Sadar",
        "Mujibnagar"
    ],
    "Moulvibazar": [
        "Barlekha",
        "Juri",
        "Kamalganj",
        "Kulaura",
        "Moulvibazar Sadar",
        "Rajnagar",
        "Sreemangal"
    ],
    "Munshiganj": [
        "Gazaria",
        "Lohajang",
        "Munshiganj Sadar",
        "Sirajdikhan",
        "Sreenagar",
        "Tongibari"
    ],
    "Mymensingh": [
        "Bhaluka",
        "Dhobaura",
        "Fulbaria",
        "Gaffargaon",
        "Gauripur",
        "Haluaghat",
        "Ishwarganj",
        "Muktagachha",
        "Mymensingh Sadar",
        "Nandail",
        "Phulpur",
        "Tara Khanda",
        "Trishal"
    ],
    "Naogaon": [
        "Atrai",
        "Badalgachhi",
        "Dhamoirhat",
        "Manda",
        "Mohadevpur",
        "Naogaon Sadar",
        "Niamatpur",
        "Patnitala",
        "Porsha",
        "Raninagar",
        "Sapahar"
    ],
    "Narail": [
        "Kalia",
        "Lohagara",
        "Narail Sadar"
    ],
    "Narayanganj": [
        "Araihazar",
        "Bandar",
        "Fatullah",
        "Narayanganj Sadar",
        "Rupganj",
        "Siddhirganj",
        "Sonargaon"
    ],
    "Narsingdi": [
        "Belabo",
        "Monohardi",
        "Narsingdi Sadar",
        "Palash",
        "Raipura",
        "Shibpur"
    ],
    "Natore": [
        "Bagatipara",
        "Baraigram",
        "Gurudaspur",
        "Lalpur",
        "Naldanga",
        "Natore Sadar",
        "Singra"
    ],
    "Netrokona": [
        "Atpara",
        "Barhatta",
        "Durgapur",
        "Kalmakanda",
        "Kendua",
        "Khaliajuri",
        "Madan",
        "Mohanganj",
        "Netrokona Sadar",
        "Purbadhala"
    ],
    "Nilphamari": [
        "Dimla",
        "Domar",
        "Jaldhaka",
        "Kishoreganj",
        "Nilphamari Sadar",
        "Saidpur"
    ],
    "Noakhali": [
        "Begumganj",
        "Chatkhil",
        "Companiganj",
        "Hatiya",
        "Kabirhat",
        "Noakhali Sadar",
        "Senbagh",
        "Sonaimuri",
        "Subarnachar"
    ],
    "Pabna": [
        "Atgharia",
        "Bera",
        "Bhangura",
        "Chatmohar",
        "Faridpur",
        "Ishwardi",
        "Pabna Sadar",
        "Santhia",
        "Sujanagar"
    ],
    "Panchagarh": [
        "Atwari",
        "Boda",
        "Debiganj",
        "Panchagarh Sadar",
        "Tetulia"
    ],
    "Patuakhali": [
        "Bauphal",
        "Dashmina",
        "Dumki",
        "Galachipa",
        "Kalapara",
        "Mirzaganj",
        "Patuakhali Sadar",
        "Rangabali"
    ],
    "Pirojpur": [
        "Bhandaria",
        "Indurkani",
        "Kawkhali",
        "Mathbaria",
        "Nazirpur",
        "Nesarabad (Swarupkati)",
        "Pirojpur Sadar"
    ],
    "Rajbari": [
        "Baliakandi",
        "Goalandaghat",
        "Kalukhali",
        "Pangsha",
        "Rajbari Sadar"
    ],
    "Rajshahi": [
        "Bagha",
        "Bagmara",
        "Boalia",
        "Charghat",
        "Durgapur",
        "Godagari",
        "Matihar",
        "Mohanpur",
        "Paba",
        "Puthia",
        "Rajpara",
        "Shah Makhdum",
        "Tanore"
    ],
    "Rangamati": [
        "Baghaichhari",
        "Barkal",
        "Belaichhari",
        "Juraichhari",
        "Kaptai",
        "Kawkhali",
        "Langadu",
        "Naniarchar",
        "Rajasthali",
        "Rangamati Sadar"
    ],
    "Rangpur": [
        "Badarganj",
        "Gangachhara",
        "Kaunia",
        "Mithapukur",
        "Pirgachha",
        "Pirganj",
        "Rangpur Sadar",
        "Taraganj"
    ],
    "Satkhira": [
        "Assasuni",
        "Debhata",
        "Kalaroa",
        "Kaliganj",
        "Satkhira Sadar",
        "Shyamnagar",
        "Tala"
    ],
    "Shariatpur": [
        "Bhedarganj",
        "Damudya",
        "Gosairhat",
        "Naria",
        "Shariatpur Sadar",
        "Zanjira"
    ],
    "Sherpur": [
        "Jhenaigati",
        "Nakla",
        "Nalitabari",
        "Sherpur Sadar",
        "Sreebardi"
    ],
    "Sirajganj": [
        "Belkuchi",
        "Chauhali",
        "Kamarkhanda",
        "Kazipur",
        "Raiganj",
        "Shahjadpur",
        "Sirajganj Sadar",
        "Tarash",
        "Ullahpara"
    ],
    "Sunamganj": [
        "Bishwambharpur",
        "Chhatak",
        "Derai",
        "Dharamapasha",
        "Dowarabazar",
        "Jagannathpur",
        "Jamalganj",
        "Madhyanagar",
        "Shantiganj",
        "Sullah",
        "Sunamganj Sadar",
        "Tahirpur"
    ],
    "Sylhet": [
        "Balaganj",
        "Beanibazar",
        "Bishwanath",
        "Companiganj",
        "Fenchuganj",
        "Golapganj",
        "Gowainghat",
        "Jaintiapur",
        "Kanaighat",
        "Osmani Nagar",
        "South Surma",
        "Sylhet Sadar",
        "Zakiganj"
    ],
    "Tangail": [
        "Basail",
        "Bhuapur",
        "Delduar",
        "Dhanbari",
        "Ghatail",
        "Gopalpur",
        "Kalihati",
        "Madhupur",
        "Mirzapur",
        "Nagarpur",
        "Sakhipur",
        "Tangail Sadar"
    ],
    "Thakurgaon": [
        "Baliadangi",
        "Haripur",
        "Pirganj",
        "Ranisankhail",
        "Thakurgaon Sadar"
    ]
};

    const BD_DISTRICTS = Object.keys(BD_LOCATIONS).sort();

    function getUpazilasForDistrict(districtName) {
        if (!districtName) return [];
        const cleanName = districtName.trim().toLowerCase();
        for (const [key, upazilas] of Object.entries(BD_LOCATIONS)) {
            if (key.toLowerCase() === cleanName) {
                return [...upazilas].sort();
            }
        }
        return [];
    }

    function populateDistrictSelect(selectElementOrId, selectedValue, placeholder) {
        if (typeof document === 'undefined') return;
        const select = typeof selectElementOrId === 'string' ? document.getElementById(selectElementOrId) : selectElementOrId;
        if (!select) return;
        const ph = placeholder || 'Select District...';
        let html = '<option value="" disabled ' + (!selectedValue ? 'selected' : '') + '>' + ph + '</option>';
        BD_DISTRICTS.forEach(function(d) {
            const isSel = (selectedValue && selectedValue.toLowerCase() === d.toLowerCase()) ? 'selected' : '';
            html += '<option value="' + d + '" ' + isSel + '>' + d + '</option>';
        });
        select.innerHTML = html;
    }

    function populateUpazilaSelect(districtName, selectElementOrId, selectedValue, placeholder) {
        if (typeof document === 'undefined') return;
        const select = typeof selectElementOrId === 'string' ? document.getElementById(selectElementOrId) : selectElementOrId;
        if (!select) return;
        const ph = placeholder || 'Select Station...';
        let html = '<option value="" disabled ' + (!selectedValue ? 'selected' : '') + '>' + ph + '</option>';
        const upazilas = getUpazilasForDistrict(districtName);
        upazilas.forEach(function(u) {
            const isSel = (selectedValue && selectedValue.toLowerCase() === u.toLowerCase()) ? 'selected' : '';
            html += '<option value="' + u + '" ' + isSel + '>' + u + '</option>';
        });
        select.innerHTML = html;
    }

    // Expose globals
    const root = (typeof window !== 'undefined') ? window : (typeof globalThis !== 'undefined' ? globalThis : this);
    root.BD_LOCATIONS = BD_LOCATIONS;
    root.BD_DISTRICTS = BD_DISTRICTS;
    root.getUpazilasForDistrict = getUpazilasForDistrict;
    root.populateDistrictSelect = populateDistrictSelect;
    root.populateUpazilaSelect = populateUpazilaSelect;

    // Offline fetch interceptor for bdapis.com to prevent network timeouts/downtime
    if (typeof root.fetch !== 'undefined') {
        const originalFetch = root.fetch;
        root.fetch = function(url, options) {
            if (typeof url === 'string') {
                if (url.includes('bdapis.com/api/v1.2/districts') || url.includes('bdapis.herokuapp.com/api/v1.2/districts')) {
                    const mockDistricts = BD_DISTRICTS.map(function(d) { return { district: d }; });
                    const responseData = { status: 200, data: mockDistricts };
                    return Promise.resolve(new Response(JSON.stringify(responseData), {
                        status: 200,
                        headers: { 'Content-Type': 'application/json' }
                    }));
                }
                const match = url.match(/bdapis\.(?:com|herokuapp\.com)\/api\/v1\.2\/district\/([^/?#]+)/);
                if (match) {
                    const districtName = decodeURIComponent(match[1]);
                    const upazilas = getUpazilasForDistrict(districtName);
                    const responseData = {
                        status: 200,
                        data: [{
                            district: districtName,
                            upazillas: upazilas
                        }]
                    };
                    return Promise.resolve(new Response(JSON.stringify(responseData), {
                        status: 200,
                        headers: { 'Content-Type': 'application/json' }
                    }));
                }
            }
            return originalFetch.apply(this, arguments);
        };
    }

    // Auto-populate when DOM is loaded
    function autoPopulateCommonSelects() {
        if (typeof document === 'undefined') return;
        const assignDistrict = document.getElementById('assign_loc_district');
        if (assignDistrict && assignDistrict.options.length <= 1) {
            populateDistrictSelect(assignDistrict);
        }
        const editLocDistrict = document.getElementById('edit_loc_district');
        if (editLocDistrict && editLocDistrict.options.length <= 1) {
            populateDistrictSelect(editLocDistrict);
        }
        const locDistrict = document.getElementById('loc-district');
        if (locDistrict && locDistrict.options.length <= 1) {
            populateDistrictSelect(locDistrict);
        }
    }

    if (typeof document !== 'undefined') {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', autoPopulateCommonSelects);
        } else {
            autoPopulateCommonSelects();
        }
    }
})();
