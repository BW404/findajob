-- Complete Nigerian States and Local Government Areas (LGAs)
-- This file contains all 36 states + FCT and their 774 LGAs

-- First, clear existing sample data
DELETE FROM nigeria_lgas;
DELETE FROM nigeria_states;

-- Insert all 36 Nigerian States + FCT
INSERT INTO nigeria_states (name, code, region) VALUES 
-- NORTH CENTRAL (7 states)
('Federal Capital Territory', 'FCT', 'north_central'),
('Benue', 'BE', 'north_central'),
('Kogi', 'KG', 'north_central'),
('Kwara', 'KW', 'north_central'),
('Nasarawa', 'NA', 'north_central'),
('Niger', 'NI', 'north_central'),
('Plateau', 'PL', 'north_central'),

-- NORTH EAST (6 states)
('Adamawa', 'AD', 'north_east'),
('Bauchi', 'BA', 'north_east'),
('Borno', 'BO', 'north_east'),
('Gombe', 'GO', 'north_east'),
('Taraba', 'TA', 'north_east'),
('Yobe', 'YO', 'north_east'),

-- NORTH WEST (7 states)
('Jigawa', 'JI', 'north_west'),
('Kaduna', 'KD', 'north_west'),
('Kano', 'KA', 'north_west'),
('Katsina', 'KT', 'north_west'),
('Kebbi', 'KE', 'north_west'),
('Sokoto', 'SO', 'north_west'),
('Zamfara', 'ZA', 'north_west'),

-- SOUTH EAST (5 states)
('Abia', 'AB', 'south_east'),
('Anambra', 'AN', 'south_east'),
('Ebonyi', 'EB', 'south_east'),
('Enugu', 'EN', 'south_east'),
('Imo', 'IM', 'south_east'),

-- SOUTH SOUTH (6 states)
('Akwa Ibom', 'AK', 'south_south'),
('Bayelsa', 'BY', 'south_south'),
('Cross River', 'CR', 'south_south'),
('Delta', 'DE', 'south_south'),
('Edo', 'ED', 'south_south'),
('Rivers', 'RI', 'south_south'),

-- SOUTH WEST (6 states)
('Ekiti', 'EK', 'south_west'),
('Lagos', 'LA', 'south_west'),
('Ogun', 'OG', 'south_west'),
('Ondo', 'ON', 'south_west'),
('Osun', 'OS', 'south_west'),
('Oyo', 'OY', 'south_west');

-- Insert all LGAs for each state
-- FEDERAL CAPITAL TERRITORY (6 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT s.id, 'Abaji' FROM nigeria_states s WHERE s.name = 'Federal Capital Territory'
UNION ALL SELECT s.id, 'Bwari' FROM nigeria_states s WHERE s.name = 'Federal Capital Territory'
UNION ALL SELECT s.id, 'Gwagwalada' FROM nigeria_states s WHERE s.name = 'Federal Capital Territory'
UNION ALL SELECT s.id, 'Kuje' FROM nigeria_states s WHERE s.name = 'Federal Capital Territory'
UNION ALL SELECT s.id, 'Municipal Area Council' FROM nigeria_states s WHERE s.name = 'Federal Capital Territory'
UNION ALL SELECT s.id, 'Kwali' FROM nigeria_states s WHERE s.name = 'Federal Capital Territory';

-- ABIA STATE (17 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Aba North'),
    ('Aba South'),
    ('Arochukwu'),
    ('Bende'),
    ('Ikwuano'),
    ('Isiala Ngwa North'),
    ('Isiala Ngwa South'),
    ('Isuikwuato'),
    ('Obi Ngwa'),
    ('Ohafia'),
    ('Osisioma'),
    ('Ugwunagbo'),
    ('Ukwa East'),
    ('Ukwa West'),
    ('Umuahia North'),
    ('Umuahia South'),
    ('Umu Nneochi')
) AS lgas(lga_name) WHERE name = 'Abia';

-- ADAMAWA STATE (21 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Demsa'),
    ('Fufure'),
    ('Ganye'),
    ('Gayuk'),
    ('Gombi'),
    ('Grie'),
    ('Hong'),
    ('Jada'),
    ('Larmurde'),
    ('Madagali'),
    ('Maiha'),
    ('Mayo Belwa'),
    ('Michika'),
    ('Mubi North'),
    ('Mubi South'),
    ('Numan'),
    ('Shelleng'),
    ('Song'),
    ('Toungo'),
    ('Yola North'),
    ('Yola South')
) AS lgas(lga_name) WHERE name = 'Adamawa';

-- AKWA IBOM STATE (31 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Abak'),
    ('Eastern Obolo'),
    ('Eket'),
    ('Esit Eket'),
    ('Essien Udim'),
    ('Etim Ekpo'),
    ('Etinan'),
    ('Ibeno'),
    ('Ibesikpo Asutan'),
    ('Ibiono-Ibom'),
    ('Ika'),
    ('Ikono'),
    ('Ikot Abasi'),
    ('Ikot Ekpene'),
    ('Ini'),
    ('Itu'),
    ('Mbo'),
    ('Mkpat-Enin'),
    ('Nsit-Atai'),
    ('Nsit-Ibom'),
    ('Nsit-Ubium'),
    ('Obot Akara'),
    ('Okobo'),
    ('Onna'),
    ('Oron'),
    ('Oruk Anam'),
    ('Udung-Uko'),
    ('Ukanafun'),
    ('Uruan'),
    ('Urue-Offong/Oruko'),
    ('Uyo')
) AS lgas(lga_name) WHERE name = 'Akwa Ibom';

-- ANAMBRA STATE (21 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Aguata'),
    ('Anambra East'),
    ('Anambra West'),
    ('Anaocha'),
    ('Awka North'),
    ('Awka South'),
    ('Ayamelum'),
    ('Dunukofia'),
    ('Ekwusigo'),
    ('Idemili North'),
    ('Idemili South'),
    ('Ihiala'),
    ('Njikoka'),
    ('Nnewi North'),
    ('Nnewi South'),
    ('Ogbaru'),
    ('Onitsha North'),
    ('Onitsha South'),
    ('Orumba North'),
    ('Orumba South'),
    ('Oyi')
) AS lgas(lga_name) WHERE name = 'Anambra';

-- BAUCHI STATE (20 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Alkaleri'),
    ('Bauchi'),
    ('Bogoro'),
    ('Damban'),
    ('Darazo'),
    ('Dass'),
    ('Gamawa'),
    ('Ganjuwa'),
    ('Giade'),
    ('Itas/Gadau'),
    ('Jama are'),
    ('Katagum'),
    ('Kirfi'),
    ('Misau'),
    ('Ningi'),
    ('Shira'),
    ('Tafawa Balewa'),
    ('Toro'),
    ('Warji'),
    ('Zaki')
) AS lgas(lga_name) WHERE name = 'Bauchi';

-- BAYELSA STATE (8 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Brass'),
    ('Ekeremor'),
    ('Kolokuma/Opokuma'),
    ('Nembe'),
    ('Ogbia'),
    ('Sagbama'),
    ('Southern Ijaw'),
    ('Yenagoa')
) AS lgas(lga_name) WHERE name = 'Bayelsa';

-- BENUE STATE (23 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Ado'),
    ('Agatu'),
    ('Apa'),
    ('Buruku'),
    ('Gboko'),
    ('Guma'),
    ('Gwer East'),
    ('Gwer West'),
    ('Katsina-Ala'),
    ('Konshisha'),
    ('Kwande'),
    ('Logo'),
    ('Makurdi'),
    ('Obi'),
    ('Ogbadibo'),
    ('Ohimini'),
    ('Oju'),
    ('Okpokwu'),
    ('Oturkpo'),
    ('Tarka'),
    ('Ukum'),
    ('Ushongo'),
    ('Vandeikya')
) AS lgas(lga_name) WHERE name = 'Benue';

-- BORNO STATE (27 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Abadam'),
    ('Askira/Uba'),
    ('Bama'),
    ('Bayo'),
    ('Biu'),
    ('Chibok'),
    ('Damboa'),
    ('Dikwa'),
    ('Gubio'),
    ('Guzamala'),
    ('Gwoza'),
    ('Hawul'),
    ('Jere'),
    ('Kaga'),
    ('Kala/Balge'),
    ('Konduga'),
    ('Kukawa'),
    ('Kwaya Kusar'),
    ('Mafa'),
    ('Magumeri'),
    ('Maiduguri'),
    ('Marte'),
    ('Mobbar'),
    ('Monguno'),
    ('Ngala'),
    ('Nganzai'),
    ('Shani')
) AS lgas(lga_name) WHERE name = 'Borno';

-- CROSS RIVER STATE (18 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Abi'),
    ('Akamkpa'),
    ('Akpabuyo'),
    ('Bakassi'),
    ('Bekwarra'),
    ('Biase'),
    ('Boki'),
    ('Calabar Municipal'),
    ('Calabar South'),
    ('Etung'),
    ('Ikom'),
    ('Obanliku'),
    ('Obubra'),
    ('Obudu'),
    ('Odukpani'),
    ('Ogoja'),
    ('Yakuur'),
    ('Yala')
) AS lgas(lga_name) WHERE name = 'Cross River';

-- DELTA STATE (25 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Aniocha North'),
    ('Aniocha South'),
    ('Bomadi'),
    ('Burutu'),
    ('Ethiope East'),
    ('Ethiope West'),
    ('Ika North East'),
    ('Ika South'),
    ('Isoko North'),
    ('Isoko South'),
    ('Ndokwa East'),
    ('Ndokwa West'),
    ('Okpe'),
    ('Oshimili North'),
    ('Oshimili South'),
    ('Patani'),
    ('Sapele'),
    ('Udu'),
    ('Ughelli North'),
    ('Ughelli South'),
    ('Ukwuani'),
    ('Uvwie'),
    ('Warri North'),
    ('Warri South'),
    ('Warri South West')
) AS lgas(lga_name) WHERE name = 'Delta';

-- EBONYI STATE (13 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Abakaliki'),
    ('Afikpo North'),
    ('Afikpo South'),
    ('Ebonyi'),
    ('Ezza North'),
    ('Ezza South'),
    ('Ikwo'),
    ('Ishielu'),
    ('Ivo'),
    ('Izzi'),
    ('Ohaozara'),
    ('Ohaukwu'),
    ('Onicha')
) AS lgas(lga_name) WHERE name = 'Ebonyi';

-- EDO STATE (18 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Akoko-Edo'),
    ('Egor'),
    ('Esan Central'),
    ('Esan North-East'),
    ('Esan South-East'),
    ('Esan West'),
    ('Etsako Central'),
    ('Etsako East'),
    ('Etsako West'),
    ('Igueben'),
    ('Ikpoba Okha'),
    ('Oovia'),
    ('Oredo'),
    ('Orhionmwon'),
    ('Ovia North-East'),
    ('Ovia South-West'),
    ('Owan East'),
    ('Owan West')
) AS lgas(lga_name) WHERE name = 'Edo';

-- EKITI STATE (16 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Ado Ekiti'),
    ('Efon'),
    ('Ekiti East'),
    ('Ekiti South-West'),
    ('Ekiti West'),
    ('Emure'),
    ('Gbonyin'),
    ('Ido Osi'),
    ('Ijero'),
    ('Ikere'),
    ('Ikole'),
    ('Ilejemeje'),
    ('Irepodun/Ifelodun'),
    ('Ise/Orun'),
    ('Moba'),
    ('Oye')
) AS lgas(lga_name) WHERE name = 'Ekiti';

-- ENUGU STATE (17 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Aninri'),
    ('Awgu'),
    ('Enugu East'),
    ('Enugu North'),
    ('Enugu South'),
    ('Ezeagu'),
    ('Igbo Etiti'),
    ('Igbo Eze North'),
    ('Igbo Eze South'),
    ('Isi Uzo'),
    ('Nkanu East'),
    ('Nkanu West'),
    ('Nsukka'),
    ('Oji River'),
    ('Udenu'),
    ('Udi'),
    ('Uzo Uwani')
) AS lgas(lga_name) WHERE name = 'Enugu';

-- GOMBE STATE (11 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Akko'),
    ('Balanga'),
    ('Billiri'),
    ('Dukku'),
    ('Funakaye'),
    ('Gombe'),
    ('Kaltungo'),
    ('Kwami'),
    ('Nafada'),
    ('Shongom'),
    ('Yamaltu/Deba')
) AS lgas(lga_name) WHERE name = 'Gombe';

-- IMO STATE (27 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Aboh Mbaise'),
    ('Ahiazu Mbaise'),
    ('Ehime Mbano'),
    ('Ezinihitte'),
    ('Ideato North'),
    ('Ideato South'),
    ('Ihitte/Uboma'),
    ('Ikeduru'),
    ('Isiala Mbano'),
    ('Isu'),
    ('Mbaitoli'),
    ('Ngor Okpala'),
    ('Njaba'),
    ('Nkwerre'),
    ('Nwangele'),
    ('Obowo'),
    ('Oguta'),
    ('Ohaji/Egbema'),
    ('Okigwe'),
    ('Orlu'),
    ('Orsu'),
    ('Oru East'),
    ('Oru West'),
    ('Owerri Municipal'),
    ('Owerri North'),
    ('Owerri West'),
    ('Unuimo')
) AS lgas(lga_name) WHERE name = 'Imo';

-- JIGAWA STATE (27 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Auyo'),
    ('Babura'),
    ('Biriniwa'),
    ('Birnin Kudu'),
    ('Buji'),
    ('Dutse'),
    ('Gagarawa'),
    ('Garki'),
    ('Gumel'),
    ('Guri'),
    ('Gwaram'),
    ('Gwiwa'),
    ('Hadejia'),
    ('Jahun'),
    ('Kafin Hausa'),
    ('Kazaure'),
    ('Kiri Kasama'),
    ('Kiyawa'),
    ('Kaugama'),
    ('Maigatari'),
    ('Malam Madori'),
    ('Miga'),
    ('Ringim'),
    ('Roni'),
    ('Sule Tankarkar'),
    ('Taura'),
    ('Yankwashi')
) AS lgas(lga_name) WHERE name = 'Jigawa';

-- KADUNA STATE (23 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Birnin Gwari'),
    ('Chikun'),
    ('Giwa'),
    ('Igabi'),
    ('Ikara'),
    ('Jaba'),
    ('Jema a'),
    ('Kachia'),
    ('Kaduna North'),
    ('Kaduna South'),
    ('Kagarko'),
    ('Kajuru'),
    ('Kaura'),
    ('Kauru'),
    ('Kubau'),
    ('Kudan'),
    ('Lere'),
    ('Makarfi'),
    ('Sabon Gari'),
    ('Sanga'),
    ('Soba'),
    ('Zangon Kataf'),
    ('Zaria')
) AS lgas(lga_name) WHERE name = 'Kaduna';

-- KANO STATE (44 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Ajingi'),
    ('Albasu'),
    ('Bagwai'),
    ('Bebeji'),
    ('Bichi'),
    ('Bunkure'),
    ('Dala'),
    ('Dambatta'),
    ('Dawakin Kudu'),
    ('Dawakin Tofa'),
    ('Doguwa'),
    ('Fagge'),
    ('Gabasawa'),
    ('Garko'),
    ('Garun Mallam'),
    ('Gaya'),
    ('Gezawa'),
    ('Gwale'),
    ('Gwarzo'),
    ('Kabo'),
    ('Kano Municipal'),
    ('Karaye'),
    ('Kibiya'),
    ('Kiru'),
    ('Kumbotso'),
    ('Kunchi'),
    ('Kura'),
    ('Madobi'),
    ('Makoda'),
    ('Minjibir'),
    ('Nasarawa'),
    ('Rano'),
    ('Rimin Gado'),
    ('Rogo'),
    ('Shanono'),
    ('Sumaila'),
    ('Takali'),
    ('Tarauni'),
    ('Tofa'),
    ('Tsanyawa'),
    ('Tudun Wada'),
    ('Ungogo'),
    ('Warawa'),
    ('Wudil')
) AS lgas(lga_name) WHERE name = 'Kano';

-- KATSINA STATE (34 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Bakori'),
    ('Batagarawa'),
    ('Batsari'),
    ('Baure'),
    ('Bindawa'),
    ('Charanchi'),
    ('Dandume'),
    ('Danja'),
    ('Dan Musa'),
    ('Daura'),
    ('Dutsi'),
    ('Dutsin Ma'),
    ('Faskari'),
    ('Funtua'),
    ('Ingawa'),
    ('Jibia'),
    ('Kafur'),
    ('Kaita'),
    ('Kankara'),
    ('Kankia'),
    ('Katsina'),
    ('Kurfi'),
    ('Kusada'),
    ('Mai Adua'),
    ('Malumfashi'),
    ('Mani'),
    ('Mashi'),
    ('Matazu'),
    ('Musawa'),
    ('Rimi'),
    ('Sabuwa'),
    ('Safana'),
    ('Sandamu'),
    ('Zango')
) AS lgas(lga_name) WHERE name = 'Katsina';

-- KEBBI STATE (21 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Aleiro'),
    ('Arewa Dandi'),
    ('Argungu'),
    ('Augie'),
    ('Bagudo'),
    ('Birnin Kebbi'),
    ('Bunza'),
    ('Dandi'),
    ('Fakai'),
    ('Gwandu'),
    ('Jega'),
    ('Kalgo'),
    ('Koko/Besse'),
    ('Maiyama'),
    ('Ngaski'),
    ('Sakaba'),
    ('Shanga'),
    ('Suru'),
    ('Wasagu/Danko'),
    ('Yauri'),
    ('Zuru')
) AS lgas(lga_name) WHERE name = 'Kebbi';

-- KOGI STATE (21 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Adavi'),
    ('Ajaokuta'),
    ('Ankpa'),
    ('Bassa'),
    ('Dekina'),
    ('Ibaji'),
    ('Idah'),
    ('Igalamela Odolu'),
    ('Ijumu'),
    ('Kabba/Bunu'),
    ('Kogi'),
    ('Lokoja'),
    ('Mopa Muro'),
    ('Ofu'),
    ('Ogori/Magongo'),
    ('Okehi'),
    ('Okene'),
    ('Olamaboro'),
    ('Omala'),
    ('Yagba East'),
    ('Yagba West')
) AS lgas(lga_name) WHERE name = 'Kogi';

-- KWARA STATE (16 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Asa'),
    ('Baruten'),
    ('Edu'),
    ('Ekiti'),
    ('Ifelodun'),
    ('Ilorin East'),
    ('Ilorin South'),
    ('Ilorin West'),
    ('Irepodun'),
    ('Isin'),
    ('Kaiama'),
    ('Moro'),
    ('Offa'),
    ('Oke Ero'),
    ('Oyun'),
    ('Pategi')
) AS lgas(lga_name) WHERE name = 'Kwara';

-- LAGOS STATE (20 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Agege'),
    ('Ajeromi-Ifelodun'),
    ('Alimosho'),
    ('Amuwo-Odofin'),
    ('Apapa'),
    ('Badagry'),
    ('Epe'),
    ('Eti Osa'),
    ('Ibeju-Lekki'),
    ('Ifako-Ijaiye'),
    ('Ikeja'),
    ('Ikorodu'),
    ('Kosofe'),
    ('Lagos Island'),
    ('Lagos Mainland'),
    ('Mushin'),
    ('Ojo'),
    ('Oshodi-Isolo'),
    ('Shomolu'),
    ('Surulere')
) AS lgas(lga_name) WHERE name = 'Lagos';

-- NASARAWA STATE (13 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Akwanga'),
    ('Awe'),
    ('Doma'),
    ('Karu'),
    ('Keana'),
    ('Keffi'),
    ('Kokona'),
    ('Lafia'),
    ('Nasarawa'),
    ('Nasarawa Egon'),
    ('Obi'),
    ('Toto'),
    ('Wamba')
) AS lgas(lga_name) WHERE name = 'Nasarawa';

-- NIGER STATE (25 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Agaie'),
    ('Agwara'),
    ('Bida'),
    ('Borgu'),
    ('Bosso'),
    ('Chanchaga'),
    ('Edati'),
    ('Gbako'),
    ('Gurara'),
    ('Katcha'),
    ('Kontagora'),
    ('Lapai'),
    ('Lavun'),
    ('Magama'),
    ('Mariga'),
    ('Mashegu'),
    ('Mokwa'),
    ('Moya'),
    ('Paikoro'),
    ('Rafi'),
    ('Rijau'),
    ('Shiroro'),
    ('Suleja'),
    ('Tafa'),
    ('Wushishi')
) AS lgas(lga_name) WHERE name = 'Niger';

-- OGUN STATE (20 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Abeokuta North'),
    ('Abeokuta South'),
    ('Ado-Odo/Ota'),
    ('Egbado North'),
    ('Egbado South'),
    ('Ewekoro'),
    ('Ifo'),
    ('Ijebu East'),
    ('Ijebu North'),
    ('Ijebu North East'),
    ('Ijebu Ode'),
    ('Ikenne'),
    ('Imeko Afon'),
    ('Ipokia'),
    ('Obafemi Owode'),
    ('Odeda'),
    ('Odogbolu'),
    ('Ogun Waterside'),
    ('Remo North'),
    ('Shagamu')
) AS lgas(lga_name) WHERE name = 'Ogun';

-- ONDO STATE (18 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Akoko North-East'),
    ('Akoko North-West'),
    ('Akoko South-West'),
    ('Akoko South-East'),
    ('Akure North'),
    ('Akure South'),
    ('Ese Odo'),
    ('Idanre'),
    ('Ifedore'),
    ('Ilaje'),
    ('Ile Oluji/Okeigbo'),
    ('Irele'),
    ('Odigbo'),
    ('Okitipupa'),
    ('Ondo East'),
    ('Ondo West'),
    ('Ose'),
    ('Owo')
) AS lgas(lga_name) WHERE name = 'Ondo';

-- OSUN STATE (30 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Atakunmosa East'),
    ('Atakunmosa West'),
    ('Aiyedaade'),
    ('Aiyedire'),
    ('Boluwaduro'),
    ('Boripe'),
    ('Ede North'),
    ('Ede South'),
    ('Egbedore'),
    ('Ejigbo'),
    ('Ife Central'),
    ('Ife East'),
    ('Ife North'),
    ('Ife South'),
    ('Ifedayo'),
    ('Ifelodun'),
    ('Ila'),
    ('Ilesa East'),
    ('Ilesa West'),
    ('Irepodun'),
    ('Irewole'),
    ('Isokan'),
    ('Iwo'),
    ('Obokun'),
    ('Odo Otin'),
    ('Ola Oluwa'),
    ('Olorunda'),
    ('Oriade'),
    ('Orolu'),
    ('Osogbo')
) AS lgas(lga_name) WHERE name = 'Osun';

-- OYO STATE (33 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Afijio'),
    ('Akinyele'),
    ('Atiba'),
    ('Atisbo'),
    ('Egbeda'),
    ('Ibadan North'),
    ('Ibadan North-East'),
    ('Ibadan North-West'),
    ('Ibadan South-East'),
    ('Ibadan South-West'),
    ('Ibarapa Central'),
    ('Ibarapa East'),
    ('Ibarapa North'),
    ('Ido'),
    ('Irepo'),
    ('Iseyin'),
    ('Itesiwaju'),
    ('Iwajowa'),
    ('Kajola'),
    ('Lagelu'),
    ('Ogbomosho North'),
    ('Ogbomosho South'),
    ('Ogo Oluwa'),
    ('Olorunsogo'),
    ('Oluyole'),
    ('Ona Ara'),
    ('Orelope'),
    ('Ori Ire'),
    ('Oyo'),
    ('Oyo East'),
    ('Saki East'),
    ('Saki West'),
    ('Surulere')
) AS lgas(lga_name) WHERE name = 'Oyo';

-- PLATEAU STATE (17 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Barkin Ladi'),
    ('Bassa'),
    ('Jos East'),
    ('Jos North'),
    ('Jos South'),
    ('Kanam'),
    ('Kanke'),
    ('Langtang North'),
    ('Langtang South'),
    ('Mangu'),
    ('Mikang'),
    ('Pankshin'),
    ('Qua an Pan'),
    ('Riyom'),
    ('Shendam'),
    ('Wase'),
    ('Bokkos')
) AS lgas(lga_name) WHERE name = 'Plateau';

-- RIVERS STATE (23 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Abua/Odual'),
    ('Ahoada East'),
    ('Ahoada West'),
    ('Akuku-Toru'),
    ('Andoni'),
    ('Asari-Toru'),
    ('Bonny'),
    ('Degema'),
    ('Eleme'),
    ('Emuoha'),
    ('Etche'),
    ('Gokana'),
    ('Ikwerre'),
    ('Khana'),
    ('Obio/Akpor'),
    ('Ogba/Egbema/Ndoni'),
    ('Ogu/Bolo'),
    ('Okrika'),
    ('Omuma'),
    ('Opobo/Nkoro'),
    ('Oyigbo'),
    ('Port Harcourt'),
    ('Tai')
) AS lgas(lga_name) WHERE name = 'Rivers';

-- SOKOTO STATE (23 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Binji'),
    ('Bodinga'),
    ('Dange Shuni'),
    ('Gada'),
    ('Goronyo'),
    ('Gudu'),
    ('Gwadabawa'),
    ('Illela'),
    ('Isa'),
    ('Kebbe'),
    ('Kware'),
    ('Rabah'),
    ('Sabon Birni'),
    ('Shagari'),
    ('Silame'),
    ('Sokoto North'),
    ('Sokoto South'),
    ('Tambuwal'),
    ('Tangaza'),
    ('Tureta'),
    ('Wamako'),
    ('Wurno'),
    ('Yabo')
) AS lgas(lga_name) WHERE name = 'Sokoto';

-- TARABA STATE (16 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Ardo Kola'),
    ('Bali'),
    ('Donga'),
    ('Gashaka'),
    ('Gassol'),
    ('Ibi'),
    ('Jalingo'),
    ('Karim Lamido'),
    ('Kumi'),
    ('Lau'),
    ('Sardauna'),
    ('Takum'),
    ('Ussa'),
    ('Wukari'),
    ('Yorro'),
    ('Zing')
) AS lgas(lga_name) WHERE name = 'Taraba';

-- YOBE STATE (17 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Bade'),
    ('Bursari'),
    ('Damaturu'),
    ('Fika'),
    ('Fune'),
    ('Geidam'),
    ('Gujba'),
    ('Gulani'),
    ('Jakusko'),
    ('Karasuwa'),
    ('Machina'),
    ('Nangere'),
    ('Nguru'),
    ('Potiskum'),
    ('Tarmuwa'),
    ('Yunusari'),
    ('Yusufari')
) AS lgas(lga_name) WHERE name = 'Yobe';

-- ZAMFARA STATE (14 LGAs)
INSERT INTO nigeria_lgas (state_id, name) 
SELECT id, lga_name FROM nigeria_states, (VALUES 
    ('Anka'),
    ('Bakura'),
    ('Birnin Magaji/Kiyaw'),
    ('Bukkuyum'),
    ('Bungudu'),
    ('Gummi'),
    ('Gusau'),
    ('Kaura Namoda'),
    ('Maradun'),
    ('Maru'),
    ('Shinkafi'),
    ('Talata Mafara'),
    ('Chafe'),
    ('Zurmi')
) AS lgas(lga_name) WHERE name = 'Zamfara';

-- Create indexes for better performance
CREATE INDEX idx_nigeria_states_region ON nigeria_states(region);
CREATE INDEX idx_nigeria_lgas_state_name ON nigeria_lgas(state_id, name);
CREATE INDEX idx_nigeria_lgas_name ON nigeria_lgas(name);

-- Update statistics
ANALYZE TABLE nigeria_states;
ANALYZE TABLE nigeria_lgas;