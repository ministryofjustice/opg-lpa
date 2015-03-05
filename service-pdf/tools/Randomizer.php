<?php

class Randomizer
{
    const ALPHA_ONLY = 1;
    const ALPHA_NUMBER = 2;
    const ALPHA_NUMBER_SYMBOL = 3;
    const HEX_NUMBER = 4;
    const EMAIL_LOCAL_PART = 5;
    
    
    protected function rInt($type, $params)
    {
        static $statics = array();
        switch($type) {
            case 'random':
                return rand($params['min'], $params['max']);
                break;
            case 'seq':
                if(isset($statics[$params['name']])) {
                    $statics[$params['name']]++;
                }
                else {
                    $statics[$params['name']] = $params['start'];
                }
                return $statics[$params['name']];
            default:
                return null; 
        }
    }
    
    protected function rTitle()
    {
        $list = array(null, 'Ms','Miss','Mrs','Mr','Dr','Prof','Hon','Capt');
        $idx = rand(0, count($list)-1);
        return $list[$idx];
    }
    
    protected function rForename()
    {
        $list = array('Harry', 'Oliver', 'Jack', 'Charlie', 'Jacob', 'Thomas', 'Alfie', 'Riley', 'William', 'James', 'Joshua', 'George', 'Ethan', 'Noah', 'Samuel', 'Daniel', 'Oscar', 'Max', 'Muhammad', 'Leo', 'Tyler', 'Joseph', 'Archie', 'Henry', 'Lucas', 'Mohammed', 'Alexander', 'Dylan', 'Logan', 'Isaac', 'Mason', 'Benjamin', 'Jake', 'Finley', 'Harrison', 'Edward', 'Jayden', 'Freddie', 'Adam', 'Zachary', 'Sebastian', 'Ryan', 'Lewis', 'Theo', 'Luke', 'Harley', 'Matthew', 'Harvey', 'Toby', 'Liam', 'Callum', 'Arthur', 'Michael', 'Jenson', 'Tommy', 'Nathan', 'Bobby', 'Connor', 'David', 'Mohammad', 'Luca', 'Charles', 'Kai', 'Jamie', 'Alex', 'Blake', 'Frankie', 'Reuben', 'Aaron', 'Dexter', 'Jude', 'Leon', 'Ollie', 'Stanley', 'Elliot', 'Gabriel', 'Cameron', 'Owen', 'Louie', 'Aiden', 'Louis', 'Elijah', 'Finlay', 'Rhys', 'Caleb', 'Evan', 'Frederick', 'Hugo', 'Kian', 'Sonny', 'Seth', 'Kayden', 'Taylor', 'Kyle', 'Elliott', 'Robert', 'Theodore', 'Bailey', 'Rory', 'Ellis', 'Joel', 'Bradley', 'Hayden', 'John', 'Ronnie', 'Olly', 'Ibrahim', 'Austin', 'Albert', 'Billy', 'Ashton', 'Christopher', 'Jackson', 'Felix', 'Ayaan', 'Dominic', 'Corey', 'Ben', 'Nathaniel', 'Aidan', 'Muhammed', 'Reece', 'Cody', 'Sam', 'Maxwell', 'Yusuf', 'Patrick', 'Tobias', 'Jasper', 'Jakub', 'Finn', 'Kaiden', 'Roman', 'Tristan', 'Carter', 'Zac', 'Rowan', 'Morgan', 'Ali', 'Jay', 'Teddy', 'Anthony', 'Syed', 'Filip', 'Eli', 'Flynn', 'Joe', 'Reggie', 'Nicholas', 'Spencer', 'Cole', 'Levi', 'Andrew', 'Ewan', 'Zack', 'Brandon', 'Ahmed', 'Alfred', 'Abdullah', 'Maximilian', 'Milo', 'Zain', 'Layton', 'Xavier', 'Jason', 'Zak', 'Leighton', 'Marcus', 'Marley', 'Jonathan', 'Kieran', 'Beau', 'Kacper', 'Ruben', 'Declan', 'Joey', 'Mohamed', 'Alan', 'Kevin', 'Jonah', 'Oskar', 'Abdul', 'Vincent', 'Peter', 'Jaiden', 'Miles', 'Rocco', 'Albie', 'Hamza', 'Lukas', 'Jensen', 'Jaxon', 'Ralph', 'Rio', 'Zach', 'Tom', 'Shay', 'Jesse', 'Fraser', 'Lennon', 'Myles', 'Rayyan', 'Brody', 'Rohan', 'Danny', 'Maximus', 'Preston', 'Adrian', 'Mckenzie', 'Jordan', 'Barnaby', 'Cooper', 'Musa', 'Mark', 'Sean', 'Ashley', 'Oakley', 'Chase', 'Yahya', 'Christian', 'Fletcher', 'Travis', 'Dominik', 'Mustafa', 'Zakariya', 'Frank', 'Omar', 'Ted', 'Vinnie', 'Emmanuel', 'Leonardo', 'Rayan', 'Malachi', 'Eric', 'Ryley', 'Raphael', 'Rufus', 'Freddy', 'Hunter', 'Sidney', 'Kye', 'Conor', 'Josh', 'Aryan', 'Hassan', 'Junior', 'Arlo', 'Elias', 'Richard', 'Robin', 'Ismail', 'Kenzie', 'Marcel', 'Eesa', 'Euan', 'Francis', 'Jan', 'Arjun', 'Aston', 'Martin', 'Paul', 'Rafael', 'Casey', 'Lincoln', 'Cian', 'Zayn', 'Cayden', 'Rupert', 'Bentley', 'Lenny', 'Philip', 'Scott', 'Rylan', 'Simon', 'Victor', 'Xander', 'Caiden', 'Hudson', 'Romeo', 'Umar', 'Finnley', 'Olivier', 'Isaiah', 'Rylee', 'Aleksander', 'Niall', 'Phoenix', 'Josiah', 'Timothy', 'Harris', 'Micah', 'Mitchell', 'Wilfred', 'Amir', 'Brodie', 'Jimmy', 'Bilal', 'Zane', 'Antoni', 'Brooklyn', 'Damian', 'Fabian', 'Jeremiah', 'Szymon', 'Tomas', 'Patryk', 'Hector', 'Stephen', 'Jaden', 'Troy', 'Asher', 'Nicolas', 'Sami', 'Douglas', 'Mylo', 'Dawid', 'Will', 'Cohen', 'Ahmad', 'Solomon', 'Eddie', 'Justin', 'Stefan', 'Lorenzo', 'Mateusz', 'Isa', 'Julian', 'Jai', 'Monty', 'Henley', 'Osian', 'Coby', 'Haris', 'Steven', 'Zachariah', 'Grayson', 'Marshall', 'Rafferty', 'Harri', 'Luka', 'Hasan', 'Ibraheem', 'Ayden', 'Leonard', 'Regan', 'Aarav', 'Casper', 'Kaleb', 'Dillon', 'Heath', 'Nico', 'Otis', 'Zayan', 'Amaan', 'Ivan', 'Buddy', 'Robbie', 'Lee', 'Curtis', 'Ezra', 'Gethin', 'Montgomery', 'Shane', 'Devon', 'Maison', 'Luis', 'Carson', 'Jac', 'Tate', 'Benedict', 'Kaden', 'Kane', 'Keegan', 'Leyton', 'Eden', 'Mackenzie', 'Otto', 'Brian', 'Dean', 'Azaan', 'Enzo', 'Ciaran', 'Ernest', 'Maksymilian', 'Tomos', 'Barney', 'Clayton', 'Marco', 'Brayden', 'Caden', 'Kyran', 'Malakai', 'Kyron', 'Laurence', 'Ilyas', 'Michal', 'Ronan', 'Hugh', 'Lucian', 'Mikolaj', 'Tyler-James', 'Hussain', 'Quinn', 'Warren', 'Alistair', 'Armaan', 'Dante', 'Angus', 'Cassius', 'Charley', 'Hari', 'Jared', 'Lawrence', 'Nate', 'Calvin', 'Ezekiel', 'Haydn', 'Khalid', 'Ayman', 'Jakob', 'Rehan', 'Bertie', 'Cruz', 'Kyan', 'Deacon', 'Dennis', 'Drew', 'Parker', 'Ayan', 'Hashim', 'Wiktor', 'Zakaria', 'Zion', 'Aadam', 'Damien', 'Dougie', 'Ioan', 'Kamil', 'Kristian', 'Idris', 'Johnny', 'Leland', 'Malik', 'Subhan', 'Nikodem', 'Noel', 'Oliwier', 'Rudy', 'Yaseen', 'Franklin', 'Hamish', 'Jaxson', 'Rex', 'Caelan', 'Imran', 'Krish', 'Shaun', 'Wyatt', 'Yuvraj', 'Anas', 'Aron', 'Cai', 'Denis', 'Edwin', 'Zayd', 'Aayan', 'Matteo', 'Woody', 'Haider', 'Igor', 'Jaydon', 'Lloyd', 'Amari', 'Bruno', 'Kobe', 'Keaton', 'Lawson', 'Antonio', 'Archibald', 'Brendan', 'Fergus', 'Ismaeel', 'Mikey', 'Archer', 'Cory', 'Frazer', 'Kobi', 'Milan', 'Rafe', 'Jonas', 'Prince', 'Daniyal', 'Diego', 'Faris', 'Jamal', 'Lachlan', 'Santiago', 'Bryan', 'Henri', 'Issac', 'Jeremy', 'Maxim', 'Maximillian', 'River', 'Amelia', 'Olivia', 'Jessica', 'Emily', 'Lily', 'Ava', 'Mia', 'Isla', 'Sophie', 'Isabella', 'Evie', 'Ruby', 'Poppy', 'Grace', 'Sophia', 'Chloe', 'Isabelle', 'Ella', 'Freya', 'Charlotte', 'Scarlett', 'Daisy', 'Lola', 'Eva', 'Holly', 'Millie', 'Lucy', 'Phoebe', 'Layla', 'Maisie', 'Sienna', 'Alice', 'Lilly', 'Florence', 'Ellie', 'Erin', 'Imogen', 'Elizabeth', 'Molly', 'Summer', 'Megan', 'Hannah', 'Sofia', 'Abigail', 'Jasmine', 'Lexi', 'Matilda', 'Rosie', 'Lacey', 'Emma', 'Amelie', 'Gracie', 'Maya', 'Hollie', 'Georgia', 'Emilia', 'Evelyn', 'Bella', 'Brooke', 'Amber', 'Eliza', 'Amy', 'Eleanor', 'Leah', 'Esme', 'Katie', 'Harriet', 'Anna', 'Willow', 'Elsie', 'Zara', 'Annabelle', 'Bethany', 'Faith', 'Madison', 'Isabel', 'Martha', 'Rose', 'Julia', 'Paige', 'Maryam', 'Maddison', 'Heidi', 'Mollie', 'Niamh', 'Skye', 'Aisha', 'Ivy', 'Darcey', 'Francesca', 'Zoe', 'Keira', 'Tilly', 'Maria', 'Sarah', 'Lydia', 'Caitlin', 'Isobel', 'Sara', 'Violet', 'Alexis', 'Lexie', 'Lauren', 'Mya', 'Seren', 'Victoria', 'Darcy', 'Rebecca', 'Annabel', 'Eloise', 'Maisy', 'Lottie', 'Fatima', 'Beatrice', 'Lara', 'Alexandra', 'Tia', 'Laila', 'Nicole', 'Gabriella', 'Nevaeh', 'Darcie', 'Iris', 'Nancy', 'Aaliyah', 'Annie', 'Libby', 'Lois', 'Alisha', 'Maja', 'Zainab', 'Leila', 'Lyla', 'Eve', 'Kayla', 'Savannah', 'Lena', 'Hope', 'Naomi', 'Laura', 'Angel', 'Miley', 'India', 'Faye', 'Zahra', 'Elise', 'Alicia', 'Madeleine', 'Natalia', 'Eden', 'Lillie', 'Connie', 'Aimee', 'Robyn', 'Orla', 'Esther', 'Georgina', 'Scarlet', 'Neve', 'Mila', 'Alyssa', 'Alexa', 'Anya', 'Emelia', 'Pippa', 'Yasmin', 'Abbie', 'Jessie', 'Beau', 'Betsy', 'Felicity', 'Sadie', 'Amira', 'Frankie', 'Aleena', 'Nina', 'Arabella', 'Elsa', 'Anaya', 'Clara', 'Macie', 'Tabitha', 'Hanna', 'Mariam', 'Elena', 'Zuzanna', 'Amina', 'Autumn', 'Lucia', 'Khadija', 'Alexia', 'Melissa', 'Nadia', 'Ayla', 'Macey', 'Jemima', 'Jennifer', 'Lacie', 'Skyla', 'Khadijah', 'Bonnie', 'Georgie', 'Lily-Rose', 'Safa', 'Aoife', 'Kaitlyn', 'Honey', 'Lana', 'Penelope', 'Alana', 'Ayesha', 'Ebony', 'Katherine', 'Taylor', 'Amelia-Rose', 'Catherine', 'Lily-Mae', 'Maia', 'Rachel', 'Zoya', 'Edith', 'Inaaya', 'Lucie', 'Tegan', 'Caitlyn', 'Cerys', 'Emmie', 'Thea', 'Tallulah', 'Cara', 'Hafsa', 'Natalie', 'April', 'Ariana', 'Josephine', 'Elodie', 'Michelle', 'Amaya', 'Delilah', 'Edie', 'Mary', 'Sasha', 'Ffion', 'Ellie-May', 'Josie', 'Demi', 'Natasha', 'Rosa', 'Maggie', 'Polly', 'Gabriela', 'Tiana', 'Hallie', 'Kara', 'Belle', 'Lily-May', 'Lyra', 'Macy', 'Syeda', 'Olive', 'Courtney', 'Esmee', 'Hana', 'Ellie-Mae', 'Casey', 'Lila', 'Constance', 'Destiny', 'Myla', 'Aminah', 'Indie', 'Beatrix', 'Maddie', 'Ellen', 'Esmae', 'Lilly-May', 'Claudia', 'Kiera', 'Talia', 'Louisa', 'Bethan', 'Jorgie', 'Liliana', 'Harper', 'Gabrielle', 'Melody', 'Alesha', 'Emilie', 'Samantha', 'Kate', 'Louise', 'Alicja', 'Izabella', 'Aria', 'Evangeline', 'Oliwia', 'Aliyah', 'Maisey', 'Annalise', 'Carys', 'Nia', 'Cora', 'Jade', 'Nicola', 'Lilly-Mae', 'Inaya', 'Philippa', 'Beth', 'Hazel', 'Penny', 'Tiffany', 'Kayleigh', 'Mabel', 'Annabella', 'Imaan', 'Ria', 'Amara', 'Iqra', 'Tianna', 'Lilly-Rose', 'Sydney', 'Kelsey', 'Pearl', 'Stephanie', 'Vanessa', 'Chelsea', 'Kyla', 'Halima', 'Inayah', 'Peyton', 'Charlie', 'Isobelle', 'Kyra', 'Madeline', 'Zaynab', 'Harmony', 'Anastasia', 'Leyla', 'Luna', 'Valentina', 'Ciara', 'Crystal', 'Milly', 'Helena', 'Aleksandra', 'Aurora', 'Kitty', 'Nikola', 'Lillian', 'Aliza', 'Amirah', 'Halle', 'Kacey', 'Serena', 'Zofia', 'Ella-Rose', 'Charley', 'Laiba', 'Miriam', 'Danielle', 'Fatimah', 'Erica', 'Sapphire', 'Eliana', 'Stella', 'Aiza', 'Verity', 'Kiara', 'Lilia', 'Daniella', 'Liberty', 'Morgan', 'Priya', 'Diya', 'Tulisa', 'Noor', 'Skylar', 'Wiktoria', 'Angelina', 'Aurelia', 'Ellie-Mai', 'Joanna', 'Shannon', 'Ada', 'Gracie-Mae', 'Ana', 'Tillie', 'Aleeza', 'Simran', 'Aaminah', 'Katelyn', 'Khloe', 'Tayla', 'Hafsah', 'Alina', 'Iyla', 'Callie', 'Carmen', 'Elisa', 'Genevieve', 'Jenna', 'Lilah', 'Sana', 'Flora', 'Maizie', 'Ophelia', 'Ashleigh', 'Ava-Rose', 'Elissa', 'Haleema', 'Nell', 'Iona', 'Teagan', 'Diana', 'Hattie', 'Hayley', 'Malaika', 'Piper', 'Roxanne', 'Evie-Mae', 'Jasmin', 'Tara', 'Ameera', 'Anabelle', 'Arianna', 'Isha', 'Lillie-Mae', 'Paris', 'Pixie', 'Katy', 'Lacey-Mae', 'Livia', 'Riya', 'Ela', 'Miya', 'Poppie', 'Arya', 'Minnie', 'Safaa', 'Cassie', 'Iman', 'Myah', 'Alyssia', 'Athena', 'Eshal', 'Jorja', 'Salma', 'Alayna', 'Keeley', 'Nyla', 'Bailey', 'Maci', 'Ruth', 'Antonia', 'Aya', 'Elisha', 'Saskia', 'Angelica', 'Betty', 'Kimberley', 'Mia-Rose', 'Milena', 'Renee', 'Tamara', 'Hermione', 'Kacie', 'Princess', 'Cleo', 'Saffron', 'Amanda', 'Anais', 'Farrah', 'Marwa', 'Sky', 'Alishba', 'Fleur', 'Karolina', 'Manha', 'Dolly', 'Isra', 'Michaela', 'Safiya', 'Adriana', 'Alba', 'Aliya', 'Lola-Rose', 'Adele', 'Bianca', 'Billie', 'Kira', 'Sian', 'Sylvie', 'Brianna', 'Evie-Rose', 'Frances', 'Kaya', 'Marnie', 'May', 'Audrey', 'Christina', 'Liyana', 'Mae');
        $idx = rand(0, count($list)-1);
        return $list[$idx];
    }
    
    protected function rSurname()
    {
        $list = array('Smith', 'Johnson', 'Williams', 'Jones', 'Brown', 'Davis', 'Miller', 'Wilson', 'Moore', 'Taylor', 'Anderson', 'Thomas', 'Jackson', 'White', 'Harris', 'Martin', 'Thompson', 'Garcia', 'Martinez', 'Robinson', 'Clark', 'Rodriguez', 'Lewis', 'Lee', 'Walker', 'Hall', 'Allen', 'Young', 'Hernandez', 'King', 'Wright', 'Lopez', 'Hill', 'Scott', 'Green', 'Adams', 'Baker', 'Gonzalez', 'Nelson', 'Carter', 'Mitchell', 'Perez', 'Roberts', 'Turner', 'Phillips', 'Campbell', 'Parker', 'Evans', 'Edwards', 'Collins', 'Stewart', 'Sanchez', 'Morris', 'Rogers', 'Reed', 'Cook', 'Morgan', 'Bell', 'Murphy', 'Bailey', 'Rivera', 'Cooper', 'Richardson', 'Cox', 'Howard', 'Ward', 'Torres', 'Peterson', 'Gray', 'Ramirez', 'James', 'Watson', 'Brooks', 'Kelly', 'Sanders', 'Price', 'Bennett', 'Wood', 'Barnes', 'Ross', 'Henderson', 'Coleman', 'Jenkins', 'Perry', 'Powell', 'Long', 'Patterson', 'Hughes', 'Flores', 'Washington', 'Butler', 'Simmons', 'Foster', 'Gonzales', 'Bryant', 'Alexander', 'Russell', 'Griffin', 'Diaz', 'Hayes', 'Myers', 'Ford', 'Hamilton', 'Graham', 'Sullivan', 'Wallace', 'Woods', 'Cole', 'West', 'Jordan', 'Owens', 'Reynolds', 'Fisher', 'Ellis', 'Harrison', 'Gibson', 'Mcdonald', 'Cruz', 'Marshall', 'Ortiz', 'Gomez', 'Murray', 'Freeman', 'Wells', 'Webb', 'Simpson', 'Stevens', 'Tucker', 'Porter', 'Hunter', 'Hicks', 'Crawford', 'Henry', 'Boyd', 'Mason', 'Morales', 'Kennedy', 'Warren', 'Dixon', 'Ramos', 'Reyes', 'Burns', 'Gordon', 'Shaw', 'Holmes', 'Rice', 'Robertson', 'Hunt', 'Black', 'Daniels', 'Palmer', 'Mills', 'Nichols', 'Grant', 'Knight', 'Ferguson', 'Rose', 'Stone', 'Hawkins', 'Dunn', 'Perkins', 'Hudson', 'Spencer', 'Gardner', 'Stephens', 'Payne', 'Pierce', 'Berry', 'Matthews', 'Arnold', 'Wagner', 'Willis', 'Ray', 'Watkins', 'Olson', 'Carroll', 'Duncan', 'Snyder', 'Hart', 'Cunningham', 'Bradley', 'Lane', 'Andrews', 'Ruiz', 'Harper', 'Fox', 'Riley', 'Armstrong', 'Carpenter', 'Weaver', 'Greene', 'Lawrence', 'Elliott', 'Chavez', 'Sims', 'Austin', 'Peters', 'Kelley', 'Franklin', 'Lawson', 'Fields', 'Gutierrez', 'Ryan', 'Schmidt', 'Carr', 'Vasquez', 'Castillo', 'Wheeler', 'Chapman', 'Oliver', 'Montgomery', 'Richards', 'Williamson', 'Johnston', 'Banks', 'Meyer', 'Bishop', 'Mccoy', 'Howell', 'Alvarez', 'Morrison', 'Hansen', 'Fernandez', 'Garza', 'Harvey', 'Little', 'Burton', 'Stanley', 'Nguyen', 'George', 'Jacobs', 'Reid', 'Kim', 'Fuller', 'Lynch', 'Dean', 'Gilbert', 'Garrett', 'Romero', 'Welch', 'Larson', 'Frazier', 'Burke', 'Hanson', 'Day', 'Mendoza', 'Moreno', 'Bowman', 'Medina', 'Fowler', 'Brewer', 'Hoffman', 'Carlson', 'Silva', 'Pearson', 'Holland', 'Douglas', 'Fleming', 'Jensen', 'Vargas', 'Byrd', 'Davidson', 'Hopkins', 'May', 'Terry', 'Herrera', 'Wade', 'Soto', 'Walters', 'Curtis', 'Neal', 'Caldwell', 'Lowe', 'Jennings', 'Barnett', 'Graves', 'Jimenez', 'Horton', 'Shelton', 'Barrett', 'O\'brien', 'Castro', 'Sutton', 'Gregory', 'Mckinney', 'Lucas', 'Miles', 'Craig', 'Rodriquez', 'Chambers', 'Holt', 'Lambert', 'Fletcher', 'Watts', 'Bates', 'Hale', 'Rhodes', 'Pena', 'Beck', 'Newman', 'Haynes', 'Mcdaniel', 'Mendez', 'Bush', 'Vaughn', 'Parks', 'Dawson', 'Santiago', 'Norris', 'Hardy', 'Love', 'Steele', 'Curry', 'Powers', 'Schultz', 'Barker', 'Guzman', 'Page', 'Munoz', 'Ball', 'Keller', 'Chandler', 'Weber', 'Leonard', 'Walsh', 'Lyons', 'Ramsey', 'Wolfe', 'Schneider', 'Mullins', 'Benson', 'Sharp', 'Bowen', 'Daniel', 'Barber', 'Cummings', 'Hines', 'Baldwin', 'Griffith', 'Valdez', 'Hubbard', 'Salazar', 'Reeves', 'Warner', 'Stevenson', 'Burgess', 'Santos', 'Tate', 'Cross', 'Garner', 'Mann', 'Mack', 'Moss', 'Thornton', 'Dennis', 'Mcgee', 'Farmer', 'Delgado', 'Aguilar', 'Vega', 'Glover', 'Manning', 'Cohen', 'Harmon', 'Rodgers', 'Robbins', 'Newton', 'Todd', 'Blair', 'Higgins', 'Ingram', 'Reese', 'Cannon', 'Strickland', 'Townsend', 'Potter', 'Goodwin', 'Walton', 'Rowe', 'Hampton', 'Ortega', 'Patton', 'Swanson', 'Joseph', 'Francis', 'Goodman', 'Maldonado', 'Yates', 'Becker', 'Erickson', 'Hodges', 'Rios', 'Conner', 'Adkins', 'Webster', 'Norman', 'Malone', 'Hammond', 'Flowers', 'Cobb', 'Moody', 'Quinn', 'Blake', 'Maxwell', 'Pope', 'Floyd', 'Osborne', 'Paul', 'Mccarthy', 'Guerrero', 'Lindsey', 'Estrada', 'Sandoval', 'Gibbs', 'Tyler', 'Gross', 'Fitzgerald', 'Stokes', 'Doyle', 'Sherman', 'Saunders', 'Wise', 'Colon', 'Gill', 'Alvarado', 'Greer', 'Padilla', 'Simon', 'Waters', 'Nunez', 'Ballard', 'Schwartz', 'Mcbride', 'Houston', 'Christensen', 'Klein', 'Pratt', 'Briggs', 'Parsons', 'Mclaughlin', 'Zimmerman', 'French', 'Buchanan', 'Moran', 'Copeland', 'Roy', 'Pittman', 'Brady', 'Mccormick', 'Holloway', 'Brock', 'Poole', 'Frank', 'Logan', 'Owen', 'Bass', 'Marsh', 'Drake', 'Wong', 'Jefferson', 'Park', 'Morton', 'Abbott', 'Sparks', 'Patrick', 'Norton', 'Huff', 'Clayton', 'Massey', 'Lloyd', 'Figueroa', 'Carson', 'Bowers', 'Roberson', 'Barton', 'Tran', 'Lamb', 'Harrington', 'Casey', 'Boone', 'Cortez', 'Clarke', 'Mathis', 'Singleton', 'Wilkins', 'Cain', 'Bryan', 'Underwood', 'Hogan', 'Mckenzie', 'Collier', 'Luna', 'Phelps', 'Mcguire', 'Allison', 'Bridges', 'Wilkerson', 'Nash', 'Summers', 'Atkins', 'Wilcox', 'Pitts', 'Conley', 'Marquez', 'Burnett', 'Richard', 'Cochran', 'Chase', 'Davenport', 'Hood', 'Gates', 'Clay', 'Ayala', 'Sawyer', 'Roman', 'Vazquez', 'Dickerson', 'Hodge', 'Acosta', 'Flynn', 'Espinoza', 'Nicholson', 'Monroe', 'Wolf', 'Morrow', 'Kirk', 'Randall', 'Anthony', 'Whitaker', 'O\'connor', 'Skinner', 'Ware', 'Molina', 'Kirby', 'Huffman', 'Bradford', 'Charles', 'Gilmore', 'Dominguez', 'O\'neal', 'Bruce', 'Lang', 'Combs', 'Kramer', 'Heath', 'Hancock', 'Gallagher', 'Gaines', 'Shaffer', 'Short', 'Wiggins', 'Mathews', 'Mcclain', 'Fischer', 'Wall', 'Small', 'Melton', 'Hensley', 'Bond', 'Dyer', 'Cameron', 'Grimes', 'Contreras', 'Christian', 'Wyatt', 'Baxter', 'Snow', 'Mosley', 'Shepherd', 'Larsen', 'Hoover', 'Beasley', 'Glenn', 'Petersen', 'Whitehead', 'Meyers', 'Keith', 'Garrison', 'Vincent', 'Shields', 'Horn', 'Savage', 'Olsen', 'Schroeder', 'Hartman', 'Woodard', 'Mueller', 'Kemp', 'Deleon', 'Booth', 'Patel', 'Calhoun', 'Wiley', 'Eaton', 'Cline', 'Navarro', 'Harrell', 'Lester', 'Humphrey', 'Parrish', 'Duran', 'Hutchinson', 'Hess', 'Dorsey', 'Bullock', 'Robles', 'Beard', 'Dalton', 'Avila', 'Vance', 'Rich', 'Blackwell', 'York', 'Johns', 'Blankenship', 'Trevino', 'Salinas', 'Campos', 'Pruitt', 'Moses', 'Callahan', 'Golden', 'Montoya', 'Hardin', 'Guerra', 'Mcdowell', 'Carey', 'Stafford', 'Gallegos', 'Henson', 'Wilkinson', 'Booker', 'Merritt', 'Miranda', 'Atkinson', 'Orr', 'Decker', 'Hobbs', 'Preston', 'Tanner', 'Knox', 'Pacheco', 'Stephenson', 'Glass', 'Rojas', 'Serrano', 'Marks', 'Hickman', 'English', 'Sweeney', 'Strong', 'Prince', 'Mcclure', 'Conway', 'Walter', 'Roth', 'Maynard', 'Farrell', 'Lowery', 'Hurst', 'Nixon', 'Weiss', 'Trujillo', 'Ellison', 'Sloan', 'Juarez', 'Winters', 'Mclean', 'Randolph', 'Leon', 'Boyer', 'Villarreal', 'Mccall', 'Gentry', 'Carrillo', 'Kent', 'Ayers', 'Lara', 'Shannon', 'Sexton', 'Pace', 'Hull', 'Leblanc', 'Browning', 'Velasquez', 'Leach', 'Chang', 'House', 'Sellers', 'Herring', 'Noble', 'Foley', 'Bartlett', 'Mercado', 'Landry', 'Durham', 'Walls', 'Barr', 'Mckee', 'Bauer', 'Rivers', 'Everett', 'Bradshaw', 'Pugh', 'Velez', 'Rush', 'Estes', 'Dodson', 'Morse', 'Sheppard', 'Weeks', 'Camacho', 'Bean', 'Barron', 'Livingston', 'Middleton', 'Spears', 'Branch', 'Blevins', 'Chen', 'Kerr', 'Mcconnell', 'Hatfield', 'Harding', 'Ashley', 'Solis', 'Herman', 'Frost', 'Giles', 'Blackburn', 'William', 'Pennington', 'Woodward', 'Finley', 'Mcintosh', 'Koch', 'Best', 'Solomon', 'Mccullough', 'Dudley', 'Nolan', 'Blanchard', 'Rivas', 'Brennan', 'Mejia', 'Kane', 'Benton', 'Joyce', 'Buckley', 'Haley', 'Valentine', 'Maddox', 'Russo', 'Mcknight', 'Buck', 'Moon', 'Mcmillan', 'Crosby', 'Berg', 'Dotson', 'Mays', 'Roach', 'Church', 'Chan', 'Richmond', 'Meadows', 'Faulkner', 'O\'neill', 'Knapp', 'Kline', 'Barry', 'Ochoa', 'Jacobson', 'Gay', 'Avery', 'Hendricks', 'Horne', 'Shepard', 'Hebert', 'Cherry', 'Cardenas', 'Mcintyre', 'Whitney', 'Waller', 'Holman', 'Donaldson', 'Cantu', 'Terrell', 'Morin', 'Gillespie', 'Fuentes', 'Tillman', 'Sanford', 'Bentley', 'Peck', 'Key', 'Salas', 'Rollins', 'Gamble', 'Dickson', 'Battle', 'Santana', 'Cabrera', 'Cervantes', 'Howe', 'Hinton', 'Hurley', 'Spence', 'Zamora', 'Yang', 'Mcneil', 'Suarez', 'Case', 'Petty', 'Gould', 'Mcfarland', 'Sampson', 'Carver', 'Bray', 'Rosario', 'Macdonald', 'Stout', 'Hester', 'Melendez', 'Dillon', 'Farley', 'Hopper', 'Galloway', 'Potts', 'Bernard', 'Joyner', 'Stein', 'Aguirre', 'Osborn', 'Mercer', 'Bender', 'Franco', 'Rowland', 'Sykes', 'Benjamin', 'Travis', 'Pickett', 'Crane', 'Sears', 'Mayo', 'Dunlap', 'Hayden', 'Wilder', 'Mckay', 'Coffey', 'Mccarty', 'Ewing', 'Cooley', 'Vaughan', 'Bonner', 'Cotton', 'Holder', 'Stark', 'Ferrell', 'Cantrell', 'Fulton', 'Lynn', 'Lott', 'Calderon', 'Rosa', 'Pollard', 'Hooper', 'Burch', 'Mullen', 'Fry', 'Riddle', 'Levy', 'David', 'Duke', 'O\'donnell', 'Guy', 'Michael', 'Britt', 'Frederick', 'Daugherty', 'Berger', 'Dillard', 'Alston', 'Jarvis', 'Frye', 'Riggs', 'Chaney', 'Odom', 'Duffy', 'Fitzpatrick', 'Valenzuela', 'Merrill', 'Mayer', 'Alford', 'Mcpherson', 'Acevedo', 'Donovan', 'Barrera', 'Albert', 'Cote', 'Reilly', 'Compton', 'Raymond', 'Mooney', 'Mcgowan', 'Craft', 'Cleveland', 'Clemons', 'Wynn', 'Nielsen', 'Baird', 'Stanton', 'Snider', 'Rosales', 'Bright', 'Witt', 'Stuart', 'Hays', 'Holden', 'Rutledge', 'Kinney', 'Clements', 'Castaneda', 'Slater', 'Hahn', 'Emerson', 'Conrad', 'Burks', 'Delaney', 'Pate', 'Lancaster', 'Sweet', 'Justice', 'Tyson', 'Sharpe', 'Whitfield', 'Talley', 'Macias', 'Irwin', 'Burris', 'Ratliff', 'Mccray', 'Madden', 'Kaufman', 'Beach', 'Goff', 'Cash', 'Bolton', 'Mcfadden', 'Levine', 'Good', 'Byers', 'Kirkland', 'Kidd', 'Workman', 'Carney', 'Dale', 'Mcleod', 'Holcomb', 'England', 'Finch', 'Head', 'Burt', 'Hendrix', 'Sosa', 'Haney', 'Franks', 'Sargent', 'Nieves', 'Downs', 'Rasmussen', 'Bird', 'Hewitt', 'Lindsay', 'Le', 'Foreman', 'Valencia', 'O\'neil', 'Delacruz', 'Vinson', 'Dejesus', 'Hyde', 'Forbes', 'Gilliam', 'Guthrie', 'Wooten', 'Huber', 'Barlow', 'Boyle', 'Mcmahon', 'Buckner', 'Rocha', 'Puckett', 'Langley', 'Knowles', 'Cooke', 'Velazquez', 'Whitley', 'Noel', 'Vang');
        $idx = rand(0, count($list)-1);
        return $list[$idx];
    }
    
    protected function rRoadname()
    {
        $list = array('High Street', 'Station Road', 'Main Street', 'Park Road', 'Church Road', 'Church Street', 'London Road', 'Victoria Road', 'Green Lane', 'Manor Road', 'Church Lane', 'Park Avenue', 'The Avenue', 'The Crescent', 'Queens Road', 'New Road', 'Grange Road', 'Kings Road', 'Kingsway', 'Windsor Road', 'Highfield Road', 'Mill Lane', 'Alexander Road', 'York Road', 'St. John\'s Road', 'Main Road', 'Broadway', 'King Street', 'The Green', 'Springfield Road', 'George Street', 'Park Lane', 'Victoria Street', 'Albert Road', 'Queensway', 'New Street', 'Queen Street', 'West Street', 'North Street', 'Manchester Road', 'The Grove', 'Richmond Road', 'Grove Road', 'South Street', 'School Lane', 'The Drive', 'North Road', 'Stanley Road', 'Chester Road', 'Mill Road');
        $idx = rand(0, count($list)-1);
        return $list[$idx];
    }
    
    protected function rPostcode()
    {
        $area = array(
                    'AB', 'AL', 'B', 'BA', 'BB', 'BD', 'BH', 'BL', 'BN', 'BR', 'BS', 'BT', 'CA', 'CB', 'CF', 'CH', 'CM', 'CO', 'CR', 'CT', 'CV', 'CW',
                    'DA', 'DD', 'DE', 'DG', 'DH', 'DL', 'DN', 'DT', 'DY', 'E', 'EC', 'EH', 'EN', 'EX', 'FK', 'FY', 'G', 'GL', 'GU', 'HA', 'HD', 'HG',
                    'HP', 'HR', 'HS', 'HU', 'HX', 'IG', 'IP', 'IV', 'KA', 'KT', 'KW', 'KY', 'L', 'LA', 'LD', 'LE', 'LL', 'LN', 'LS', 'LU', 'M', 'ME',
                    'MK', 'ML', 'N', 'NE', 'NG', 'NN', 'NP', 'NR', 'NW', 'OL', 'OX', 'PA', 'PE', 'PH', 'PL', 'PO', 'PR', 'RG', 'RH', 'RM', 'S', 'SA',
                    'SE', 'SG', 'SK', 'SL', 'SM', 'SN', 'SO', 'SP', 'SR', 'SS', 'ST', 'SW', 'SY', 'TA', 'TD', 'TF', 'TN', 'TQ', 'TR', 'TS', 'TW', 'UB',
                    'W', 'WA', 'WC', 'WD', 'WF', 'WN', 'WR', 'WS', 'WV', 'YO', 'ZE'
        );
        
        $list = array_merge(range('A','Z'), range(0,9), array(' '));
        $postcode = $area[rand(0, count($area)-1)] . 
                rand(0,9) . 
                $this->random(array('', $this->random(range(0,9)), $this->random(range('A','Z')))) . 
                ' ' .
                rand(0,9).
                $this->random(range('A','Z')) .
                $this->random(range('A','Z'));
        
        return $postcode;
    }
    
    protected function rAddr()
    {
        $addr = array();
        $addr['address1'] = (string)rand(1,999).' '.$this->rRoadname();
        $townCountyList = $this->townCountys();
        $tc = $townCountyList[rand(0, count($townCountyList)-1)];
        $addr['address2'] = $tc[0];
        $addr['address3'] = $tc[1].', '.$tc[2];
        $addr['postcode'] = $this->rPostcode();
        
        return $addr;
    }
    
    protected function rPhone()
    {
        return '+'.(string)rand(1, 999).' '.(string)rand(10, 999).' '.sprintf('%06d', rand(0,999999));
    }
    
    protected function rEmail()
    {
        $domainList = array('co.uk', 'com', 'gov.uk', 'org.uk', 'net', 'io', 'me');
        return $this->rString(array('min'=>2, 'max'=>10), self::EMAIL_LOCAL_PART).'@'.$this->rString(array('min'=>2, 'max'=>10), self::ALPHA_ONLY).'.'.$domainList[rand(0, count($domainList)-1)];
    }
    
    protected function rDxNumber()
    {
        return $this->rInt('random', array('min'=>1,'max'=>'99999'));
    }
    
    protected function rDxExchange()
    {
        return $this->rString(10, self::ALPHA_ONLY);
    }
    
    protected function rDate($can_be_null = false)
    {
        $date = date('c', rand(strtotime('-100 years'), time()-rand(500, 10000)));
        if($can_be_null) {
            return $this->random(array(null, $date, $date));
        }
        else {
            return $date;
        }
    }

    protected function rDob()
    {
        $date = new DateTime();
        $date = date('c', rand(strtotime('-90 years'), strtotime('-18 years')));
        return $date;
    }
    
    /**
     * Pick random item from an array
     * @param array $list
     * @param bool $randomList - when true, return random subset of the array. 
     * @return single value|array
     */
    protected function random($list, $randomList=false)
    {
        if(!is_array($list) || empty($list)) return;
        
        if(!$randomList) {
            return $list[rand(0, count($list)-1)];
        }
        else {
            $totalItems = count($list);
            $totalPicks = rand(1, $totalItems);
            $indexes = [];
            do {
                $indexes[rand(0, $totalItems-1)] = 1;
            }while(count($indexes) < $totalPicks);
            
            $returnList = [];
            foreach($indexes as $idx=>$v) {
                $returnList[] = $list[$idx];
            }
            return $returnList;
        }
    }
    
    protected function rString($length, $type=self::ALPHA_ONLY)
    {
        $alph = range('a', 'z');
        $digt = range('0','9');
        $hex = array_merge($digt, range('a','f'));
        $alph_digt = array_merge($alph, $digt);
        $symb = (array)'!@£$%^&*()_+-=[]{}:|;\,./<>?';
        
        $str = "";
        switch($type) {
            case self::ALPHA_ONLY:
                $list = $alph;
                break;
            case self::ALPHA_NUMBER:
                $list = $alph_digt;
                break;
            case self::HEX_NUMBER:
                $list = $hex;
                break;
            case self::ALPHA_NUMBER_SYMBOL:
                $list = array_merge($alph_digt, $symb);
                break;
            case self::EMAIL_LOCAL_PART:
                $list = $alph_digt; 
        }
        
        if(is_numeric($length)) {
            for($i=0; $i<$length; $i++) {
                $str .= $list[rand(0, count($list)-1)];
            }
        }
        elseif(is_array($length)) {
            $len = rand($length['min'], $length['max']);
            for($i=0; $i<$len; $i++) {
                $str .= $list[rand(0, count($list)-1)];
            }
        }
        
        return $str;
    }
    
    protected function rWords($count = null)
    {
        $str  =$this->lorem();
        $list = preg_split('/[,.\s]+/', $str);
        $chosenWords = array();
        
        if(!$count) {
            for($i=0; $i<rand(0, count($list)); $i++) {
                $chosenWords[] = $list[rand(0, count($list)-1)];
            }
        }
        else {
            if(is_array($count)) {
                $c = rand($count['min'], $count['max']);
                for($i=0; $i<$c; $i++) {
                    $chosenWords[] = $list[rand(0, count($list)-1)];
                }
            }
            else {
                for($i=0; $i<$count; $i++) {
                    $chosenWords[] = $list[rand(0, count($list)-1)];
                }
            }
        }
        
        return implode(' ', $chosenWords);
    }
    
    protected function rText($count)
    {
        $str = str_replace("\r", "", $this->lorem());
        
        $paragraphs = explode("\n\n", $str);
        
        foreach($paragraphs as &$para) {
            $para = str_replace("\n", ' ', $para);
        }
        
        $str = implode("\n\n", $paragraphs);
        
        do {
            $startPara = rand(0, count($paragraphs)-1);
            $start = strpos($str, $paragraphs[$startPara]);
        }while($start + $count > strlen($str));
        
        $str = substr($str, $start, $count);
        
        return ltrim($str, ",.!? \n");
    }
    
    protected function lorem()
    {
        return <<<EOD
The Law of the Jungle lays down very clearly that any wolf
may, when he marries, withdraw from the Pack he belongs to. But
as soon as his cubs are old enough to stand on their feet he must
bring them to the Pack Council, which is generally held once a
month at full moon, in order that the other wolves may identify
them. After that inspection the cubs are free to run where they
please, and until they have killed their first buck no excuse is
accepted if a grown wolf of the Pack kills one of them. The
punishment is death where the murderer can be found; and if you
think for a minute you will see that this must be so.

Father Wolf waited till his cubs could run a little, and then
on the night of the Pack Meeting took them and Mowgli and Mother
Wolf to the Council Rock--a hilltop covered with stones and
boulders where a hundred wolves could hide. Akela, the great gray
Lone Wolf, who led all the Pack by strength and cunning, lay out
at full length on his rock, and below him sat forty or more wolves
of every size and color, from badger-colored veterans who could
handle a buck alone to young black three-year-olds who thought
they could. The Lone Wolf had led them for a year now. He had
fallen twice into a wolf trap in his youth, and once he had been
beaten and left for dead; so he knew the manners and customs of
men. There was very little talking at the Rock. The cubs tumbled
over each other in the center of the circle where their mothers
and fathers sat, and now and again a senior wolf would go quietly
up to a cub, look at him carefully, and return to his place on
noiseless feet. Sometimes a mother would push her cub far out
into the moonlight to be sure that he had not been overlooked.
Akela from his rock would cry: "Ye know the Law--ye know the
Law. Look well, O Wolves!" And the anxious mothers would take up
the call: "Look--look well, O Wolves!"

At last--and Mother Wolf's neck bristles lifted as the time
came--Father Wolf pushed "Mowgli the Frog," as they called him,
into the center, where he sat laughing and playing with some
pebbles that glistened in the moonlight.

Akela never raised his head from his paws, but went on with
the monotonous cry: "Look well!" A muffled roar came up from
behind the rocks--the voice of Shere Khan crying: "The cub is
mine. Give him to me. What have the Free People to do with a
man's cub?" Akela never even twitched his ears. All he said was:
"Look well, O Wolves! What have the Free People to do with the
orders of any save the Free People? Look well!"

There was a chorus of deep growls, and a young wolf in his
fourth year flung back Shere Khan's question to Akela: "What have
the Free People to do with a man's cub?" Now, the Law of the
Jungle lays down that if there is any dispute as to the right of a
cub to be accepted by the Pack, he must be spoken for by at least
two members of the Pack who are not his father and mother.

"Who speaks for this cub?" said Akela. "Among the Free People
who speaks?" There was no answer and Mother Wolf got ready for
what she knew would be her last fight, if things came to fighting.

Then the only other creature who is allowed at the Pack
Council--Baloo, the sleepy brown bear who teaches the wolf cubs
the Law of the Jungle: old Baloo, who can come and go where he
pleases because he eats only nuts and roots and honey--rose upon
his hind quarters and grunted.

"The man's cub--the man's cub?" he said. "I speak for the
man's cub. There is no harm in a man's cub. I have no gift of
words, but I speak the truth. Let him run with the Pack, and be
entered with the others. I myself will teach him."

"We need yet another," said Akela. "Baloo has spoken, and he
is our teacher for the young cubs. Who speaks besides Baloo?"

A black shadow dropped down into the circle. It was Bagheera
the Black Panther, inky black all over, but with the panther
markings showing up in certain lights like the pattern of watered
silk. Everybody knew Bagheera, and nobody cared to cross his
path; for he was as cunning as Tabaqui, as bold as the wild
buffalo, and as reckless as the wounded elephant. But he had a
voice as soft as wild honey dripping from a tree, and a skin
softer than down.

"O Akela, and ye the Free People," he purred, "I have no right
in your assembly, but the Law of the Jungle says that if there is
a doubt which is not a killing matter in regard to a new cub, the
life of that cub may be bought at a price. And the Law does not
say who may or may not pay that price. Am I right?"

"Good! Good!" said the young wolves, who are always hungry.
"Listen to Bagheera. The cub can be bought for a price. It is
the Law."

"Knowing that I have no right to speak here, I ask your
leave."

"Speak then," cried twenty voices.

"To kill a naked cub is shame. Besides, he may make better
sport for you when he is grown. Baloo has spoken in his behalf.
Now to Baloo's word I will add one bull, and a fat one, newly
killed, not half a mile from here, if ye will accept the man's cub
according to the Law. Is it difficult?"

There was a clamor of scores of voices, saying: "What matter?
He will die in the winter rains. He will scorch in the sun. What
harm can a naked frog do us? Let him run with the Pack. Where is
the bull, Bagheera? Let him be accepted." And then came Akela's
deep bay, crying: "Look well--look well, O Wolves!"

Mowgli was still deeply interested in the pebbles, and he did
not notice when the wolves came and looked at him one by one. At
last they all went down the hill for the dead bull, and only
Akela, Bagheera, Baloo, and Mowgli's own wolves were left. Shere
Khan roared still in the night, for he was very angry that Mowgli
had not been handed over to him.

"Ay, roar well," said Bagheera, under his whiskers, "for the
time will come when this naked thing will make thee roar to
another tune, or I know nothing of man."

"It was well done," said Akela. "Men and their cubs are very
wise. He may be a help in time."

"Truly, a help in time of need; for none can hope to lead the
Pack forever," said Bagheera.

Akela said nothing. He was thinking of the time that comes to
every leader of every pack when his strength goes from him and he
gets feebler and feebler, till at last he is killed by the wolves
and a new leader comes up--to be killed in his turn.

"Take him away," he said to Father Wolf, "and train him as
befits one of the Free People."

And that is how Mowgli was entered into the Seeonee Wolf Pack
for the price of a bull and on Baloo's good word.


Now you must be content to skip ten or eleven whole years, and
only guess at all the wonderful life that Mowgli led among the
wolves, because if it were written out it would fill ever so many
books. He grew up with the cubs, though they, of course, were
grown wolves almost before he was a child. And Father Wolf taught
him his business, and the meaning of things in the jungle, till
every rustle in the grass, every breath of the warm night air,
every note of the owls above his head, every scratch of a bat's
claws as it roosted for a while in a tree, and every splash of
every little fish jumping in a pool meant just as much to him as
the work of his office means to a business man. When he was not
learning he sat out in the sun and slept, and ate and went to
sleep again. When he felt dirty or hot he swam in the forest
pools; and when he wanted honey (Baloo told him that honey and
nuts were just as pleasant to eat as raw meat) he climbed up for
it, and that Bagheera showed him how to do. Bagheera would lie
out on a branch and call, "Come along, Little Brother," and at
first Mowgli would cling like the sloth, but afterward he would
fling himself through the branches almost as boldly as the gray
ape. He took his place at the Council Rock, too, when the Pack
met, and there he discovered that if he stared hard at any wolf,
the wolf would be forced to drop his eyes, and so he used to stare
for fun. At other times he would pick the long thorns out of the
pads of his friends, for wolves suffer terribly from thorns and
burs in their coats. He would go down the hillside into the
cultivated lands by night, and look very curiously at the
villagers in their huts, but he had a mistrust of men because
Bagheera showed him a square box with a drop gate so cunningly
hidden in the jungle that he nearly walked into it, and told him
that it was a trap. He loved better than anything else to go with
Bagheera into the dark warm heart of the forest, to sleep all
through the drowsy day, and at night see how Bagheera did his
killing. Bagheera killed right and left as he felt hungry, and so
did Mowgli--with one exception. As soon as he was old enough to
understand things, Bagheera told him that he must never touch
cattle because he had been bought into the Pack at the price of a
bull's life. "All the jungle is thine," said Bagheera, "and thou
canst kill everything that thou art strong enough to kill; but for
the sake of the bull that bought thee thou must never kill or eat
any cattle young or old. That is the Law of the Jungle." Mowgli
obeyed faithfully.

And he grew and grew strong as a boy must grow who does not
know that he is learning any lessons, and who has nothing in the
world to think of except things to eat.

Mother Wolf told him once or twice that Shere Khan was not a
creature to be trusted, and that some day he must kill Shere Khan.
But though a young wolf would have remembered that advice every
hour, Mowgli forgot it because he was only a boy--though he
would have called himself a wolf if he had been able to speak in
any human tongue.

Shere Khan was always crossing his path in the jungle, for as
Akela grew older and feebler the lame tiger had come to be great
friends with the younger wolves of the Pack, who followed him for
scraps, a thing Akela would never have allowed if he had dared to
push his authority to the proper bounds. Then Shere Khan would
flatter them and wonder that such fine young hunters were content
to be led by a dying wolf and a man's cub. "They tell me," Shere
Khan would say, "that at Council ye dare not look him between the
eyes." And the young wolves would growl and bristle.

Bagheera, who had eyes and ears everywhere, knew something of
this, and once or twice he told Mowgli in so many words that Shere
Khan would kill him some day. Mowgli would laugh and answer: "I
have the Pack and I have thee; and Baloo, though he is so lazy,
might strike a blow or two for my sake. Why should I be afraid?"


It was one very warm day that a new notion came to Bagheera--
born of something that he had heard. Perhaps Ikki the Porcupine
had told him; but he said to Mowgli when they were deep in the
jungle, as the boy lay with his head on Bagheera's beautiful black
skin, "Little Brother, how often have I told thee that Shere Khan
is thy enemy?"

"As many times as there are nuts on that palm," said Mowgli,
who, naturally, could not count. "What of it? I am sleepy,
Bagheera, and Shere Khan is all long tail and loud talk--like
Mao, the Peacock."

"But this is no time for sleeping. Baloo knows it; I know it;
the Pack know it; and even the foolish, foolish deer know.
Tabaqui has told thee too."

"Ho! ho!" said Mowgli. "Tabaqui came to me not long ago with
some rude talk that I was a naked man's cub and not fit to dig
pig-nuts. But I caught Tabaqui by the tail and swung him twice
against a palm-tree to teach him better manners."

"That was foolishness, for though Tabaqui is a mischief-maker,
he would have told thee of something that concerned thee closely.
Open those eyes, Little Brother. Shere Khan dare not kill thee in
the jungle. But remember, Akela is very old, and soon the day
comes when he cannot kill his buck, and then he will be leader no
more. Many of the wolves that looked thee over when thou wast
brought to the Council first are old too, and the young wolves
believe, as Shere Khan has taught them, that a man-cub has no
place with the Pack. In a little time thou wilt be a man."

"And what is a man that he should not run with his brothers?"
said Mowgli. "I was born in the jungle. I have obeyed the Law of
the Jungle, and there is no wolf of ours from whose paws I have
not pulled a thorn. Surely they are my brothers!"

Bagheera stretched himself at full length and half shut his
eyes. "Little Brother," said he, "feel under my jaw."

Mowgli put up his strong brown hand, and just under Bagheera's
silky chin, where the giant rolling muscles were all hid by the
glossy hair, he came upon a little bald spot.

"There is no one in the jungle that knows that I, Bagheera,
carry that mark--the mark of the collar; and yet, Little
Brother, I was born among men, and it was among men that my mother
died--in the cages of the king's palace at Oodeypore. It was
because of this that I paid the price for thee at the Council when
thou wast a little naked cub. Yes, I too was born among men. I
had never seen the jungle. They fed me behind bars from an iron
pan till one night I felt that I was Bagheera--the Panther--
and no man's plaything, and I broke the silly lock with one blow
of my paw and came away. And because I had learned the ways of
men, I became more terrible in the jungle than Shere Khan. Is it
not so?"

"Yes," said Mowgli, "all the jungle fear Bagheera--all
except Mowgli."

"Oh, thou art a man's cub," said the Black Panther very
tenderly. "And even as I returned to my jungle, so thou must go
back to men at last--to the men who are thy brothers--if thou
art not killed in the Council."

"But why--but why should any wish to kill me?" said Mowgli.

"Look at me," said Bagheera. And Mowgli looked at him
steadily between the eyes. The big panther turned his head away
in half a minute.

"That is why," he said, shifting his paw on the leaves. "Not
even I can look thee between the eyes, and I was born among men,
and I love thee, Little Brother. The others they hate thee
because their eyes cannot meet thine; because thou art wise;
because thou hast pulled out thorns from their feet--because
thou art a man."

"I did not know these things," said Mowgli sullenly, and he
frowned under his heavy black eyebrows.

"What is the Law of the Jungle? Strike first and then give
tongue. By thy very carelessness they know that thou art a man.
But be wise. It is in my heart that when Akela misses his next
kill--and at each hunt it costs him more to pin the buck--the
Pack will turn against him and against thee. They will hold a
jungle Council at the Rock, and then--and then--I have it!"
said Bagheera, leaping up. "Go thou down quickly to the men's
huts in the valley, and take some of the Red Flower which they
grow there, so that when the time comes thou mayest have even a
stronger friend than I or Baloo or those of the Pack that love
thee. Get the Red Flower."

By Red Flower Bagheera meant fire, only no creature in the
jungle will call fire by its proper name. Every beast lives in
deadly fear of it, and invents a hundred ways of describing it.

"The Red Flower?" said Mowgli. "That grows outside their huts
in the twilight. I will get some."

"There speaks the man's cub," said Bagheera proudly.
"Remember that it grows in little pots. Get one swiftly, and keep
it by thee for time of need."

"Good!" said Mowgli. "I go. But art thou sure, O my
Bagheera"--he slipped his arm around the splendid neck and
looked deep into the big eyes--"art thou sure that all this is
Shere Khan's doing?"

"By the Broken Lock that freed me, I am sure, Little Brother."

"Then, by the Bull that bought me, I will pay Shere Khan full
tale for this, and it may be a little over," said Mowgli, and he
bounded away.

"That is a man. That is all a man," said Bagheera to himself,
lying down again. "Oh, Shere Khan, never was a blacker hunting
than that frog-hunt of thine ten years ago!"

Mowgli was far and far through the forest, running hard, and
his heart was hot in him. He came to the cave as the evening mist
rose, and drew breath, and looked down the valley. The cubs were
out, but Mother Wolf, at the back of the cave, knew by his
breathing that something was troubling her frog.

"What is it, Son?" she said.

"Some bat's chatter of Shere Khan," he called back. "I hunt
among the plowed fields tonight," and he plunged downward through
the bushes, to the stream at the bottom of the valley. There he
checked, for he heard the yell of the Pack hunting, heard the
bellow of a hunted Sambhur, and the snort as the buck turned at
bay. Then there were wicked, bitter howls from the young wolves:
"Akela! Akela! Let the Lone Wolf show his strength. Room for
the leader of the Pack! Spring, Akela!"

The Lone Wolf must have sprung and missed his hold, for Mowgli
heard the snap of his teeth and then a yelp as the Sambhur knocked
him over with his forefoot.

He did not wait for anything more, but dashed on; and the
yells grew fainter behind him as he ran into the croplands where
the villagers lived.

"Bagheera spoke truth," he panted, as he nestled down in some
cattle fodder by the window of a hut. "To-morrow is one day both
for Akela and for me."

Then he pressed his face close to the window and watched the
fire on the hearth. He saw the husbandman's wife get up and feed
it in the night with black lumps. And when the morning came and
the mists were all white and cold, he saw the man's child pick up
a wicker pot plastered inside with earth, fill it with lumps of
red-hot charcoal, put it under his blanket, and go out to tend the
cows in the byre.

"Is that all?" said Mowgli. "If a cub can do it, there is
nothing to fear." So he strode round the corner and met the boy,
took the pot from his hand, and disappeared into the mist while
the boy howled with fear.

"They are very like me," said Mowgli, blowing into the pot as
he had seen the woman do. "This thing will die if I do not give
it things to eat"; and he dropped twigs and dried bark on the red
stuff. Halfway up the hill he met Bagheera with the morning dew
shining like moonstones on his coat.

"Akela has missed," said the Panther. "They would have killed
him last night, but they needed thee also. They were looking for
thee on the hill."

"I was among the plowed lands. I am ready. See!" Mowgli
held up the fire-pot.

"Good! Now, I have seen men thrust a dry branch into that
stuff, and presently the Red Flower blossomed at the end of it.
Art thou not afraid?"

"No. Why should I fear? I remember now--if it is not a
dream--how, before I was a Wolf, I lay beside the Red Flower,
and it was warm and pleasant."

All that day Mowgli sat in the cave tending his fire pot and
dipping dry branches into it to see how they looked. He found a
branch that satisfied him, and in the evening when Tabaqui came to
the cave and told him rudely enough that he was wanted at the
Council Rock, he laughed till Tabaqui ran away. Then Mowgli went
to the Council, still laughing.

Akela the Lone Wolf lay by the side of his rock as a sign that
the leadership of the Pack was open, and Shere Khan with his
following of scrap-fed wolves walked to and fro openly being
flattered. Bagheera lay close to Mowgli, and the fire pot was
between Mowgli's knees. When they were all gathered together,
Shere Khan began to speak--a thing he would never have dared to
do when Akela was in his prime.

"He has no right," whispered Bagheera. "Say so. He is a
dog's son. He will be frightened."

Mowgli sprang to his feet. "Free People," he cried, "does
Shere Khan lead the Pack? What has a tiger to do with our
leadership?"

"Seeing that the leadership is yet open, and being asked to
speak--" Shere Khan began.

"By whom?" said Mowgli. "Are we all jackals, to fawn on this
cattle butcher? The leadership of the Pack is with the Pack
alone."

There were yells of "Silence, thou man's cub!" "Let him
speak. He has kept our Law"; and at last the seniors of the Pack
thundered: "Let the Dead Wolf speak." When a leader of the Pack
has missed his kill, he is called the Dead Wolf as long as he
lives, which is not long.

Akela raised his old head wearily:--

"Free People, and ye too, jackals of Shere Khan, for twelve
seasons I have led ye to and from the kill, and in all that time
not one has been trapped or maimed. Now I have missed my kill.
Ye know how that plot was made. Ye know how ye brought me up to
an untried buck to make my weakness known. It was cleverly done.
Your right is to kill me here on the Council Rock, now.
Therefore, I ask, who comes to make an end of the Lone Wolf? For
it is my right, by the Law of the Jungle, that ye come one by
one."

There was a long hush, for no single wolf cared to fight Akela
to the death. Then Shere Khan roared: "Bah! What have we to do
with this toothless fool? He is doomed to die! It is the man-cub
who has lived too long. Free People, he was my meat from the
first. Give him to me. I am weary of this man-wolf folly. He
has troubled the jungle for ten seasons. Give me the man-cub, or
I will hunt here always, and not give you one bone. He is a man,
a man's child, and from the marrow of my bones I hate him!"

Then more than half the Pack yelled: "A man! A man! What has
a man to do with us? Let him go to his own place."

"And turn all the people of the villages against us?" clamored
Shere Khan. "No, give him to me. He is a man, and none of us can
look him between the eyes."

Akela lifted his head again and said, "He has eaten our food.
He has slept with us. He has driven game for us. He has broken
no word of the Law of the Jungle."

"Also, I paid for him with a bull when he was accepted. The
worth of a bull is little, but Bagheera's honor is something that
he will perhaps fight for," said Bagheera in his gentlest voice.

"A bull paid ten years ago!" the Pack snarled. "What do we
care for bones ten years old?"

"Or for a pledge?" said Bagheera, his white teeth bared under
his lip. "Well are ye called the Free People!"

"No man's cub can run with the people of the jungle," howled
Shere Khan. "Give him to me!"

"He is our brother in all but blood," Akela went on, "and ye
would kill him here! In truth, I have lived too long. Some of ye
are eaters of cattle, and of others I have heard that, under Shere
Khan's teaching, ye go by dark night and snatch children from the
villager's doorstep. Therefore I know ye to be cowards, and it is
to cowards I speak. It is certain that I must die, and my life is
of no worth, or I would offer that in the man-cub's place. But
for the sake of the Honor of the Pack,--a little matter that by
being without a leader ye have forgotten,--I promise that if ye
let the man-cub go to his own place, I will not, when my time
comes to die, bare one tooth against ye. I will die without
fighting. That will at least save the Pack three lives. More I
cannot do; but if ye will, I can save ye the shame that comes of
killing a brother against whom there is no fault--a brother
spoken for and bought into the Pack according to the Law of the
Jungle."

"He is a man--a man--a man!" snarled the Pack. And most
of the wolves began to gather round Shere Khan, whose tail was
beginning to switch.

"Now the business is in thy hands," said Bagheera to Mowgli.
"We can do no more except fight."

Mowgli stood upright--the fire pot in his hands. Then he
stretched out his arms, and yawned in the face of the Council; but
he was furious with rage and sorrow, for, wolflike, the wolves had
never told him how they hated him. "Listen you!" he cried.
"There is no need for this dog's jabber. Ye have told me so often
tonight that I am a man (and indeed I would have been a wolf with
you to my life's end) that I feel your words are true. So I do
not call ye my brothers any more, but sag [dogs], as a man should.
What ye will do, and what ye will not do, is not yours to say.
That matter is with me; and that we may see the matter more
plainly, I, the man, have brought here a little of the Red Flower
which ye, dogs, fear."

He flung the fire pot on the ground, and some of the red coals
lit a tuft of dried moss that flared up, as all the Council drew
back in terror before the leaping flames.

Mowgli thrust his dead branch into the fire till the twigs lit
and crackled, and whirled it above his head among the cowering
wolves.

"Thou art the master," said Bagheera in an undertone. "Save
Akela from the death. He was ever thy friend."

Akela, the grim old wolf who had never asked for mercy in his
life, gave one piteous look at Mowgli as the boy stood all naked,
his long black hair tossing over his shoulders in the light of the
blazing branch that made the shadows jump and quiver.

"Good!" said Mowgli, staring round slowly. "I see that ye are
dogs. I go from you to my own people--if they be my own people.
The jungle is shut to me, and I must forget your talk and your
companionship. But I will be more merciful than ye are. Because
I was all but your brother in blood, I promise that when I am a
man among men I will not betray ye to men as ye have betrayed me."
He kicked the fire with his foot, and the sparks flew up. "There
shall be no war between any of us in the Pack. But here is a debt
to pay before I go." He strode forward to where Shere Khan sat
blinking stupidly at the flames, and caught him by the tuft on his
chin. Bagheera followed in case of accidents. "Up, dog!" Mowgli
cried. "Up, when a man speaks, or I will set that coat ablaze!"

Shere Khan's ears lay flat back on his head, and he shut his
eyes, for the blazing branch was very near.

"This cattle-killer said he would kill me in the Council
because he had not killed me when I was a cub. Thus and thus,
then, do we beat dogs when we are men. Stir a whisker, Lungri,
and I ram the Red Flower down thy gullet!" He beat Shere Khan
over the head with the branch, and the tiger whimpered and whined
in an agony of fear.

"Pah! Singed jungle cat--go now! But remember when next I
come to the Council Rock, as a man should come, it will be with
Shere Khan's hide on my head. For the rest, Akela goes free to
live as he pleases. Ye will not kill him, because that is not my
will. Nor do I think that ye will sit here any longer, lolling
out your tongues as though ye were somebodies, instead of dogs
whom I drive out--thus! Go!" The fire was burning furiously at
the end of the branch, and Mowgli struck right and left round the
circle, and the wolves ran howling with the sparks burning their
fur. At last there were only Akela, Bagheera, and perhaps ten
wolves that had taken Mowgli's part. Then something began to hurt
Mowgli inside him, as he had never been hurt in his life before,
and he caught his breath and sobbed, and the tears ran down his
face.

"What is it? What is it?" he said. "I do not wish to leave
the jungle, and I do not know what this is. Am I dying,
Bagheera?"

"No, Little Brother. That is only tears such as men use,"
said Bagheera. "Now I know thou art a man, and a man's cub no
longer. The jungle is shut indeed to thee henceforward. Let them
fall, Mowgli. They are only tears." So Mowgli sat and cried as
though his heart would break; and he had never cried in all his
life before.

"Now," he said, "I will go to men. But first I must say
farewell to my mother." And he went to the cave where she lived
with Father Wolf, and he cried on her coat, while the four cubs
howled miserably.

"Ye will not forget me?" said Mowgli.

"Never while we can follow a trail," said the cubs. "Come to
the foot of the hill when thou art a man, and we will talk to
thee; and we will come into the croplands to play with thee by
night."

"Come soon!" said Father Wolf. "Oh, wise little frog, come
again soon; for we be old, thy mother and I."

"Come soon," said Mother Wolf, "little naked son of mine.
For, listen, child of man, I loved thee more than ever I loved my
cubs."

"I will surely come," said Mowgli. "And when I come it will
be to lay out Shere Khan's hide upon the Council Rock. Do not
forget me! Tell them in the jungle never to forget me!"

The dawn was beginning to break when Mowgli went down the
hillside alone, to meet those mysterious things that are called
men.


Hunting-Song of the Seeonee Pack

As the dawn was breaking the Sambhur belled
Once, twice and again!
And a doe leaped up, and a doe leaped up
From the pond in the wood where the wild deer sup.
This I, scouting alone, beheld,
Once, twice and again!

As the dawn was breaking the Sambhur belled
Once, twice and again!
And a wolf stole back, and a wolf stole back
To carry the word to the waiting pack,
And we sought and we found and we bayed on his track
Once, twice and again!

As the dawn was breaking the Wolf Pack yelled
Once, twice and again!
Feet in the jungle that leave no mark!

Eyes that can see in the dark--the dark!
Tongue--give tongue to it! Hark! O hark!
Once, twice and again!


Kaa's Hunting

His spots are the joy of the Leopard: his horns are the
Buffalo's pride.
Be clean, for the strength of the hunter is known by the
gloss of his hide.
If ye find that the Bullock can toss you, or the heavy-browed
Sambhur can gore;
Ye need not stop work to inform us: we knew it ten seasons
before.
Oppress not the cubs of the stranger, but hail them as Sister
and Brother,
For though they are little and fubsy, it may be the Bear is
their mother.
"There is none like to me!" says the Cub in the pride of his
earliest kill;
But the jungle is large and the Cub he is small. Let him
think and be still.
Maxims of Baloo

All that is told here happened some time before Mowgli was turned
out of the Seeonee Wolf Pack, or revenged himself on Shere Khan
the tiger. It was in the days when Baloo was teaching him the Law
of the Jungle. The big, serious, old brown bear was delighted to
have so quick a pupil, for the young wolves will only learn as
much of the Law of the Jungle as applies to their own pack and
tribe, and run away as soon as they can repeat the Hunting Verse
--"Feet that make no noise; eyes that can see in the dark; ears
that can hear the winds in their lairs, and sharp white teeth, all
these things are the marks of our brothers except Tabaqui the
Jackal and the Hyaena whom we hate." But Mowgli, as a man-cub,
had to learn a great deal more than this. Sometimes Bagheera the
Black Panther would come lounging through the jungle to see how
his pet was getting on, and would purr with his head against a
tree while Mowgli recited the day's lesson to Baloo. The boy
could climb almost as well as he could swim, and swim almost as
well as he could run. So Baloo, the Teacher of the Law, taught
him the Wood and Water Laws: how to tell a rotten branch from a
sound one; how to speak politely to the wild bees when he came
upon a hive of them fifty feet above ground; what to say to Mang
the Bat when he disturbed him in the branches at midday; and how
to warn the water-snakes in the pools before he splashed down
among them. None of the Jungle People like being disturbed, and
all are very ready to fly at an intruder. Then, too, Mowgli was
taught the Strangers' Hunting Call, which must be repeated aloud
till it is answered, whenever one of the Jungle-People hunts
outside his own grounds. It means, translated, "Give me leave to
hunt here because I am hungry." And the answer is, "Hunt then for
food, but not for pleasure."

All this will show you how much Mowgli had to learn by heart,
and he grew very tired of saying the same thing over a hundred
times. But, as Baloo said to Bagheera, one day when Mowgli had
been cuffed and run off in a temper, "A man's cub is a man's cub,
and he must learn all the Law of the Jungle."

"But think how small he is," said the Black Panther, who would
have spoiled Mowgli if he had had his own way. "How can his
little head carry all thy long talk?"

"Is there anything in the jungle too little to be killed? No.
That is why I teach him these things, and that is why I hit him,
very softly, when he forgets."

"Softly! What dost thou know of softness, old Iron-feet?"
Bagheera grunted. "His face is all bruised today by thy--
softness. Ugh."

"Better he should be bruised from head to foot by me who love
him than that he should come to harm through ignorance," Baloo
answered very earnestly. "I am now teaching him the Master Words
of the Jungle that shall protect him with the birds and the Snake
People, and all that hunt on four feet, except his own pack. He
can now claim protection, if he will only remember the words, from
all in the jungle. Is not that worth a little beating?"

"Well, look to it then that thou dost not kill the man-cub.
He is no tree trunk to sharpen thy blunt claws upon. But what are
those Master Words? I am more likely to give help than to ask it"
--Bagheera stretched out one paw and admired the steel-blue,
ripping-chisel talons at the end of it--"still I should like to
know."

"I will call Mowgli and he shall say them--if he will.
Come, Little Brother!"

"My head is ringing like a bee tree," said a sullen little
voice over their heads, and Mowgli slid down a tree trunk very
angry and indignant, adding as he reached the ground: "I come for
Bagheera and not for thee, fat old Baloo!"

"That is all one to me," said Baloo, though he was hurt and
grieved. "Tell Bagheera, then, the Master Words of the Jungle
that I have taught thee this day."

"Master Words for which people?" said Mowgli, delighted to
show off. "The jungle has many tongues. I know them all."

"A little thou knowest, but not much. See, O Bagheera, they
never thank their teacher. Not one small wolfling has ever come
back to thank old Baloo for his teachings. Say the word for the
Hunting-People, then--great scholar."

"We be of one blood, ye and I," said Mowgli, giving the words
the Bear accent which all the Hunting People use.

"Good. Now for the birds."

Mowgli repeated, with the Kite's whistle at the end of the
sentence.

"Now for the Snake-People," said Bagheera.

The answer was a perfectly indescribable hiss, and Mowgli
kicked up his feet behind, clapped his hands together to applaud
himself, and jumped on to Bagheera's back, where he sat sideways,
drumming with his heels on the glossy skin and making the worst
faces he could think of at Baloo.

"There--there! That was worth a little bruise," said the
brown bear tenderly. "Some day thou wilt remember me." Then he
turned aside to tell Bagheera how he had begged the Master Words
from Hathi the Wild Elephant, who knows all about these things,
and how Hathi had taken Mowgli down to a pool to get the Snake
Word from a water-snake, because Baloo could not pronounce it, and
how Mowgli was now reasonably safe against all accidents in the
jungle, because neither snake, bird, nor beast would hurt him.

"No one then is to be feared," Baloo wound up, patting his big
furry stomach with pride.

"Except his own tribe," said Bagheera, under his breath; and
then aloud to Mowgli, "Have a care for my ribs, Little Brother!
What is all this dancing up and down?"

Mowgli had been trying to make himself heard by pulling at
Bagheera's shoulder fur and kicking hard. When the two listened
to him he was shouting at the top of his voice, "And so I shall
have a tribe of my own, and lead them through the branches all day
long."

"What is this new folly, little dreamer of dreams?" said
Bagheera.

"Yes, and throw branches and dirt at old Baloo," Mowgli went
on. "They have promised me this. Ah!"

"Whoof!" Baloo's big paw scooped Mowgli off Bagheera's back,
and as the boy lay between the big fore-paws he could see the Bear
was angry.

"Mowgli," said Baloo, "thou hast been talking with the
Bandar-log--the Monkey People."

Mowgli looked at Bagheera to see if the Panther was angry too,
and Bagheera's eyes were as hard as jade stones.

"Thou hast been with the Monkey People--the gray apes--the
people without a law--the eaters of everything. That is great
shame."

"When Baloo hurt my head," said Mowgli (he was still on his
back), "I went away, and the gray apes came down from the trees
and had pity on me. No one else cared." He snuffled a little.

"The pity of the Monkey People!" Baloo snorted. "The
stillness of the mountain stream! The cool of the summer sun!
And then, man-cub?"

"And then, and then, they gave me nuts and pleasant things to
eat, and they--they carried me in their arms up to the top of
the trees and said I was their blood brother except that I had no
tail, and should be their leader some day."

"They have no leader," said Bagheera. "They lie. They have
always lied."

"They were very kind and bade me come again. Why have I never
been taken among the Monkey People? They stand on their feet as I
do. They do not hit me with their hard paws. They play all day.
Let me get up! Bad Baloo, let me up! I will play with them
again."

"Listen, man-cub," said the Bear, and his voice rumbled like
thunder on a hot night. "I have taught thee all the Law of the
Jungle for all the peoples of the jungle--except the Monkey-Folk
who live in the trees. They have no law. They are outcasts.
They have no speech of their own, but use the stolen words which
they overhear when they listen, and peep, and wait up above in
the branches. Their way is not our way. They are without
leaders. They have no remembrance. They boast and chatter and
pretend that they are a great people about to do great affairs in
the jungle, but the falling of a nut turns their minds to laughter
and all is forgotten. We of the jungle have no dealings with
them. We do not drink where the monkeys drink; we do not go where
the monkeys go; we do not hunt where they hunt; we do not die
where they die. Hast thou ever heard me speak of the Bandar-log
till today?"

"No," said Mowgli in a whisper, for the forest was very still
now Baloo had finished.

"The Jungle-People put them out of their mouths and out of
their minds. They are very many, evil, dirty, shameless, and they
desire, if they have any fixed desire, to be noticed by the Jungle
People. But we do not notice them even when they throw nuts and
filth on our heads."

He had hardly spoken when a shower of nuts and twigs spattered
down through the branches; and they could hear coughings and
howlings and angry jumpings high up in the air among the thin
branches.

"The Monkey-People are forbidden," said Baloo, "forbidden to
the Jungle-People. Remember."

"Forbidden," said Bagheera, "but I still think Baloo should
have warned thee against them."

"I--I? How was I to guess he would play with such dirt.
The Monkey People! Faugh!"

A fresh shower came down on their heads and the two trotted
away, taking Mowgli with them. What Baloo had said about the
monkeys was perfectly true. They belonged to the tree-tops, and as
beasts very seldom look up, there was no occasion for the monkeys
and the Jungle-People to cross each other's path. But whenever
they found a sick wolf, or a wounded tiger, or bear, the monkeys
would torment him, and would throw sticks and nuts at any beast
for fun and in the hope of being noticed. Then they would howl
and shriek senseless songs, and invite the Jungle-People to climb
up their trees and fight them, or would start furious battles over
nothing among themselves, and leave the dead monkeys where the
Jungle-People could see them. They were always just going to have
a leader, and laws and customs of their own, but they never did,
because their memories would not hold over from day to day, and so
they compromised things by making up a saying, "What the
Bandar-log think now the jungle will think later," and that
comforted them a great deal. None of the beasts could reach them,
but on the other hand none of the beasts would notice them, and
that was why they were so pleased when Mowgli came to play with
them, and they heard how angry Baloo was.

They never meant to do any more--the Bandar-log never mean
anything at all; but one of them invented what seemed to him a
brilliant idea, and he told all the others that Mowgli would be a
useful person to keep in the tribe, because he could weave sticks
together for protection from the wind; so, if they caught him,
they could make him teach them. Of course Mowgli, as a
woodcutter's child, inherited all sorts of instincts, and used to
make little huts of fallen branches without thinking how he came
to do it. The Monkey-People, watching in the trees, considered
his play most wonderful. This time, they said, they were really
going to have a leader and become the wisest people in the jungle
--so wise that everyone else would notice and envy them.
Therefore they followed Baloo and Bagheera and Mowgli through the
jungle very quietly till it was time for the midday nap, and
Mowgli, who was very much ashamed of himself, slept between the
Panther and the Bear, resolving to have no more to do with the
Monkey People.

The next thing he remembered was feeling hands on his legs and
arms--hard, strong, little hands--and then a swash of branches
in his face, and then he was staring down through the swaying
boughs as Baloo woke the jungle with his deep cries and Bagheera
bounded up the trunk with every tooth bared. The Bandar-log
howled with triumph and scuffled away to the upper branches where
Bagheera dared not follow, shouting: "He has noticed us! Bagheera
has noticed us. All the Jungle-People admire us for our skill and
our cunning." Then they began their flight; and the flight of the
Monkey-People through tree-land is one of the things nobody can
describe. They have their regular roads and crossroads, up hills
and down hills, all laid out from fifty to seventy or a hundred
feet above ground, and by these they can travel even at night if
necessary. Two of the strongest monkeys caught Mowgli under the
arms and swung off with him through the treetops, twenty feet at a
bound. Had they been alone they could have gone twice as fast,
but the boy's weight held them back. Sick and giddy as Mowgli was
he could not help enjoying the wild rush, though the glimpses of
earth far down below frightened him, and the terrible check and
jerk at the end of the swing over nothing but empty air brought
his heart between his teeth. His escort would rush him up a tree
till he felt the thinnest topmost branches crackle and bend under
them, and then with a cough and a whoop would fling themselves
into the air outward and downward, and bring up, hanging by their
hands or their feet to the lower limbs of the next tree.
Sometimes he could see for miles and miles across the still green
jungle, as a man on the top of a mast can see for miles across the
sea, and then the branches and leaves would lash him across the
face, and he and his two guards would be almost down to earth
again. So, bounding and crashing and whooping and yelling, the
whole tribe of Bandar-log swept along the tree-roads with Mowgli
their prisoner.

For a time he was afraid of being dropped. Then he grew angry
but knew better than to struggle, and then he began to think. The
first thing was to send back word to Baloo and Bagheera, for, at
the pace the monkeys were going, he knew his friends would be left
far behind. It was useless to look down, for he could only see
the topsides of the branches, so he stared upward and saw, far
away in the blue, Rann the Kite balancing and wheeling as he kept
watch over the jungle waiting for things to die. Rann saw that
the monkeys were carrying something, and dropped a few hundred
yards to find out whether their load was good to eat. He whistled
with surprise when he saw Mowgli being dragged up to a treetop and
heard him give the Kite call for--"We be of one blood, thou and
I." The waves of the branches closed over the boy, but Chil
balanced away to the next tree in time to see the little brown
face come up again. "Mark my trail!" Mowgli shouted. "Tell
Baloo of the Seeonee Pack and Bagheera of the Council Rock."

"In whose name, Brother?" Rann had never seen Mowgli before,
though of course he had heard of him.

"Mowgli, the Frog. Man-cub they call me! Mark my tra-il!"

The last words were shrieked as he was being swung through the
air, but Rann nodded and rose up till he looked no bigger than a
speck of dust, and there he hung, watching with his telescope eyes
the swaying of the treetops as Mowgli's escort whirled along.

"They never go far," he said with a chuckle. "They never do
what they set out to do. Always pecking at new things are the
Bandar-log. This time, if I have any eye-sight, they have pecked
down trouble for themselves, for Baloo is no fledgling and
Bagheera can, as I know, kill more than goats."

So he rocked on his wings, his feet gathered up under him, and
waited.

Meantime, Baloo and Bagheera were furious with rage and grief.
Bagheera climbed as he had never climbed before, but the thin
branches broke beneath his weight, and he slipped down, his claws
full of bark.

"Why didst thou not warn the man-cub?" he roared to poor
Baloo, who had set off at a clumsy trot in the hope of overtaking
the monkeys. "What was the use of half slaying him with blows if
thou didst not warn him?"

"Haste! O haste! We--we may catch them yet!" Baloo
panted.

"At that speed! It would not tire a wounded cow. Teacher of
the Law--cub-beater--a mile of that rolling to and fro would
burst thee open. Sit still and think! Make a plan. This is no
time for chasing. They may drop him if we follow too close."

"Arrula! Whoo! They may have dropped him already, being
tired of carrying him. Who can trust the Bandar-log? Put dead
bats on my head! Give me black bones to eat! Roll me into the
hives of the wild bees that I may be stung to death, and bury me
with the Hyaena, for I am most miserable of bears! Arulala!
Wahooa! O Mowgli, Mowgli! Why did I not warn thee against the
Monkey-Folk instead of breaking thy head? Now perhaps I may have
knocked the day's lesson out of his mind, and he will be alone in
the jungle without the Master Words."

Baloo clasped his paws over his ears and rolled to and fro
moaning.

"At least he gave me all the Words correctly a little time
ago," said Bagheera impatiently. "Baloo, thou hast neither memory
nor respect. What would the jungle think if I, the Black Panther,
curled myself up like Ikki the Porcupine, and howled?"

"What do I care what the jungle thinks? He may be dead by
now."

"Unless and until they drop him from the branches in sport, or
kill him out of idleness, I have no fear for the man-cub. He is
wise and well taught, and above all he has the eyes that make the
Jungle-People afraid. But (and it is a great evil) he is in the
power of the Bandar-log, and they, because they live in trees,
have no fear of any of our people." Bagheera licked one forepaw
thoughtfully.

"Fool that I am! Oh, fat, brown, root-digging fool that I
am," said Baloo, uncoiling himself with a jerk, "it is true what
Hathi the Wild Elephant says: `To each his own fear'; and they,
the Bandar-log, fear Kaa the Rock Snake. He can climb as well as
they can. He steals the young monkeys in the night. The whisper
of his name makes their wicked tails cold. Let us go to Kaa."

"What will he do for us? He is not of our tribe, being
footless--and with most evil eyes," said Bagheera.

"He is very old and very cunning. Above all, he is always
hungry," said Baloo hopefully. "Promise him many goats."

"He sleeps for a full month after he has once eaten. He may
be asleep now, and even were he awake what if he would rather kill
his own goats?" Bagheera, who did not know much about Kaa, was
naturally suspicious.

"Then in that case, thou and I together, old hunter, might
make him see reason." Here Baloo rubbed his faded brown shoulder
against the Panther, and they went off to look for Kaa the Rock
Python.

They found him stretched out on a warm ledge in the afternoon
sun, admiring his beautiful new coat, for he had been in
retirement for the last ten days changing his skin, and now he was
very splendid--darting his big blunt-nosed head along the
ground, and twisting the thirty feet of his body into fantastic
knots and curves, and licking his lips as he thought of his dinner
to come.

"He has not eaten," said Baloo, with a grunt of relief, as
soon as he saw the beautifully mottled brown and yellow jacket.
"Be careful, Bagheera! He is always a little blind after he has
changed his skin, and very quick to strike."

Kaa was not a poison snake--in fact he rather despised the
poison snakes as cowards--but his strength lay in his hug, and
when he had once lapped his huge coils round anybody there was no
more to be said. "Good hunting!" cried Baloo, sitting up on his
haunches. Like all snakes of his breed Kaa was rather deaf, and
did not hear the call at first. Then he curled up ready for any
accident, his head lowered.

"Good hunting for us all," he answered. "Oho, Baloo, what
dost thou do here? Good hunting, Bagheera. One of us at least
needs food. Is there any news of game afoot? A doe now, or even
a young buck? I am as empty as a dried well."

"We are hunting," said Baloo carelessly. He knew that you
must not hurry Kaa. He is too big.

"Give me permission to come with you," said Kaa. "A blow more
or less is nothing to thee, Bagheera or Baloo, but I--I have to
wait and wait for days in a wood-path and climb half a night on
the mere chance of a young ape. Psshaw! The branches are not
what they were when I was young. Rotten twigs and dry boughs are
they all."

"Maybe thy great weight has something to do with the matter,"
said Baloo.

"I am a fair length--a fair length," said Kaa with a little
pride. "But for all that, it is the fault of this new-grown
timber. I came very near to falling on my last hunt--very near
indeed--and the noise of my slipping, for my tail was not tight
wrapped around the tree, waked the Bandar-log, and they called me
most evil names."

"Footless, yellow earth-worm," said Bagheera under his
whiskers, as though he were trying to remember something.

"Sssss! Have they ever called me that?" said Kaa.

"Something of that kind it was that they shouted to us last
moon, but we never noticed them. They will say anything--even
that thou hast lost all thy teeth, and wilt not face anything
bigger than a kid, because (they are indeed shameless, these
Bandar-log)--because thou art afraid of the he-goat's horns,"
Bagheera went on sweetly.

Now a snake, especially a wary old python like Kaa, very
seldom shows that he is angry, but Baloo and Bagheera could see
the big swallowing muscles on either side of Kaa's throat ripple
and bulge.

"The Bandar-log have shifted their grounds," he said quietly.
"When I came up into the sun today I heard them whooping among the
tree-tops."

"It--it is the Bandar-log that we follow now," said Baloo,
but the words stuck in his throat, for that was the first time in
his memory that one of the Jungle-People had owned to being
interested in the doings of the monkeys.

"Beyond doubt then it is no small thing that takes two such
hunters--leaders in their own jungle I am certain--on the
trail of the Bandar-log," Kaa replied courteously, as he swelled
with curiosity.

"Indeed," Baloo began, "I am no more than the old and
sometimes very foolish Teacher of the Law to the Seeonee
wolf-cubs, and Bagheera here--"

"Is Bagheera," said the Black Panther, and his jaws shut with
a snap, for he did not believe in being humble. "The trouble is
this, Kaa. Those nut-stealers and pickers of palm leaves have
stolen away our man-cub of whom thou hast perhaps heard."

"I heard some news from Ikki (his quills make him
presumptuous) of a man-thing that was entered into a wolf pack,
but I did not believe. Ikki is full of stories half heard and
very badly told."

"But it is true. He is such a man-cub as never was," said
Baloo. "The best and wisest and boldest of man-cubs--my own
pupil, who shall make the name of Baloo famous through all the
jungles; and besides, I--we--love him, Kaa."

"Ts! Ts!" said Kaa, weaving his head to and fro. "I also
have known what love is. There are tales I could tell that--"

"That need a clear night when we are all well fed to praise
properly," said Bagheera quickly. "Our man-cub is in the hands of
the Bandar-log now, and we know that of all the Jungle-People they
fear Kaa alone."

"They fear me alone. They have good reason," said Kaa.
"Chattering, foolish, vain--vain, foolish, and chattering, are
the monkeys. But a man-thing in their hands is in no good luck.
They grow tired of the nuts they pick, and throw them down. They
carry a branch half a day, meaning to do great things with it, and
then they snap it in two. That man-thing is not to be envied.
They called me also--`yellow fish' was it not?"

"Worm--worm--earth-worm," said Bagheera, "as well as other
things which I cannot now say for shame."

"We must remind them to speak well of their master. Aaa-ssp!
We must help their wandering memories. Now, whither went they
with the cub?"

"The jungle alone knows. Toward the sunset, I believe," said
Baloo. "We had thought that thou wouldst know, Kaa."

"I? How? I take them when they come in my way, but I do not
hunt the Bandar-log, or frogs--or green scum on a water-hole,
for that matter."

"Up, Up! Up, Up! Hillo! Illo! Illo, look up, Baloo of the
Seeonee Wolf Pack!"

Baloo looked up to see where the voice came from, and there
was Rann the Kite, sweeping down with the sun shining on the
upturned flanges of his wings. It was near Rann's bedtime, but he
had ranged all over the jungle looking for the Bear and had missed
him in the thick foliage.

"What is it?" said Baloo.

"I have seen Mowgli among the Bandar-log. He bade me tell
you. I watched. The Bandar-log have taken him beyond the river
to the monkey city--to the Cold Lairs. They may stay there for
a night, or ten nights, or an hour. I have told the bats to watch
through the dark time. That is my message. Good hunting, all you
below!"

"Full gorge and a deep sleep to you, Rann," cried Bagheera.
"I will remember thee in my next kill, and put aside the head for
thee alone, O best of kites!"

"It is nothing. It is nothing. The boy held the Master Word.
I could have done no less," and Rann circled up again to his
roost.

"He has not forgotten to use his tongue," said Baloo with a
chuckle of pride. "To think of one so young remembering the
Master Word for the birds too while he was being pulled across
trees!"

"It was most firmly driven into him," said Bagheera. "But I
am proud of him, and now we must go to the Cold Lairs."

They all knew where that place was, but few of the Jungle
People ever went there, because what they called the Cold Lairs
was an old deserted city, lost and buried in the jungle, and
beasts seldom use a place that men have once used. The wild boar
will, but the hunting tribes do not. Besides, the monkeys lived
there as much as they could be said to live anywhere, and no
self-respecting animal would come within eyeshot of it except in
times of drought, when the half-ruined tanks and reservoirs held a
little water.

"It is half a night's journey--at full speed," said
Bagheera, and Baloo looked very serious. "I will go as fast as I
can," he said anxiously.

"We dare not wait for thee. Follow, Baloo. We must go on the
quick-foot--Kaa and I."

"Feet or no feet, I can keep abreast of all thy four," said
Kaa shortly. Baloo made one effort to hurry, but had to sit down
panting, and so they left him to come on later, while Bagheera
hurried forward, at the quick panther-canter. Kaa said nothing,
but, strive as Bagheera might, the huge Rock-python held level
with him. When they came to a hill stream, Bagheera gained,
because he bounded across while Kaa swam, his head and two feet of
his neck clearing the water, but on level ground Kaa made up the
distance.

"By the Broken Lock that freed me," said Bagheera, when
twilight had fallen, "thou art no slow goer!"

"I am hungry," said Kaa. "Besides, they called me speckled
frog."

"Worm--earth-worm, and yellow to boot."

"All one. Let us go on," and Kaa seemed to pour himself along
the ground, finding the shortest road with his steady eyes, and
keeping to it.

In the Cold Lairs the Monkey-People were not thinking of
Mowgli's friends at all. They had brought the boy to the Lost
City, and were very much pleased with themselves for the time.
Mowgli had never seen an Indian city before, and though this was
almost a heap of ruins it seemed very wonderful and splendid.
Some king had built it long ago on a little hill. You could still
trace the stone causeways that led up to the ruined gates where
the last splinters of wood hung to the worn, rusted hinges. Trees
had grown into and out of the walls; the battlements were tumbled
down and decayed, and wild creepers hung out of the windows of the
towers on the walls in bushy hanging clumps.

A great roofless palace crowned the hill, and the marble of
the courtyards and the fountains was split, and stained with red
and green, and the very cobblestones in the courtyard where the
king's elephants used to live had been thrust up and apart by
grasses and young trees. From the palace you could see the rows
and rows of roofless houses that made up the city looking like
empty honeycombs filled with blackness; the shapeless block of
stone that had been an idol in the square where four roads met;
the pits and dimples at street corners where the public wells once
stood, and the shattered domes of temples with wild figs sprouting
on their sides. The monkeys called the place their city, and
pretended to despise the Jungle-People because they lived in the
forest. And yet they never knew what the buildings were made for
nor how to use them. They would sit in circles on the hall of the
king's council chamber, and scratch for fleas and pretend to be
men; or they would run in and out of the roofless houses and
collect pieces of plaster and old bricks in a corner, and forget
where they had hidden them, and fight and cry in scuffling crowds,
and then break off to play up and down the terraces of the king's
garden, where they would shake the rose trees and the oranges in
sport to see the fruit and flowers fall. They explored all the
passages and dark tunnels in the palace and the hundreds of little
dark rooms, but they never remembered what they had seen and what
they had not; and so drifted about in ones and twos or crowds
telling each other that they were doing as men did. They drank at
the tanks and made the water all muddy, and then they fought over
it, and then they would all rush together in mobs and shout:
"There is no one in the jungle so wise and good and clever and
strong and gentle as the Bandar-log." Then all would begin again
till they grew tired of the city and went back to the tree-tops,
hoping the Jungle-People would notice them.

Mowgli, who had been trained under the Law of the Jungle, did
not like or understand this kind of life. The monkeys dragged him
into the Cold Lairs late in the afternoon, and instead of going to
sleep, as Mowgli would have done after a long journey, they joined
hands and danced about and sang their foolish songs. One of the
monkeys made a speech and told his companions that Mowgli's
capture marked a new thing in the history of the Bandar-log, for
Mowgli was going to show them how to weave sticks and canes
together as a protection against rain and cold. Mowgli picked up
some creepers and began to work them in and out, and the monkeys
tried to imitate; but in a very few minutes they lost interest and
began to pull their friends' tails or jump up and down on all
fours, coughing.

"I wish to eat," said Mowgli. "I am a stranger in this part
of the jungle. Bring me food, or give me leave to hunt here."

Twenty or thirty monkeys bounded away to bring him nuts and
wild pawpaws. But they fell to fighting on the road, and it was
too much trouble to go back with what was left of the fruit.
Mowgli was sore and angry as well as hungry, and he roamed through
the empty city giving the Strangers' Hunting Call from time to
time, but no one answered him, and Mowgli felt that he had reached
a very bad place indeed. "All that Baloo has said about the
Bandar-log is true," he thought to himself. "They have no Law, no
Hunting Call, and no leaders--nothing but foolish words and
little picking thievish hands. So if I am starved or killed here,
it will be all my own fault. But I must try to return to my own
jungle. Baloo will surely beat me, but that is better than
chasing silly rose leaves with the Bandar-log."

No sooner had he walked to the city wall than the monkeys
pulled him back, telling him that he did not know how happy he
was, and pinching him to make him grateful. He set his teeth and
said nothing, but went with the shouting monkeys to a terrace
above the red sandstone reservoirs that were half-full of rain
water. There was a ruined summer-house of white marble in the
center of the terrace, built for queens dead a hundred years ago.
The domed roof had half fallen in and blocked up the underground
passage from the palace by which the queens used to enter. But
the walls were made of screens of marble tracery--beautiful
milk-white fretwork, set with agates and cornelians and jasper and
lapis lazuli, and as the moon came up behind the hill it shone
through the open work, casting shadows on the ground like black
velvet embroidery. Sore, sleepy, and hungry as he was, Mowgli
could not help laughing when the Bandar-log began, twenty at a
time, to tell him how great and wise and strong and gentle they
were, and how foolish he was to wish to leave them. "We are
great. We are free. We are wonderful. We are the most wonderful
people in all the jungle! We all say so, and so it must be true,"
they shouted. "Now as you are a new listener and can carry our
words back to the Jungle-People so that they may notice us in
future, we will tell you all about our most excellent selves."
Mowgli made no objection, and the monkeys gathered by hundreds and
hundreds on the terrace to listen to their own speakers singing
the praises of the Bandar-log, and whenever a speaker stopped for
want of breath they would all shout together: "This is true; we
all say so." Mowgli nodded and blinked, and said "Yes" when they
asked him a question, and his head spun with the noise. "Tabaqui
the Jackal must have bitten all these people," he said to himself,
"and now they have madness. Certainly this is dewanee, the
madness. Do they never go to sleep? Now there is a cloud coming
to cover that moon. If it were only a big enough cloud I might
try to run away in the darkness. But I am tired."

That same cloud was being watched by two good friends in the
ruined ditch below the city wall, for Bagheera and Kaa, knowing
well how dangerous the Monkey-People were in large numbers, did
not wish to run any risks. The monkeys never fight unless they
are a hundred to one, and few in the jungle care for those odds.

"I will go to the west wall," Kaa whispered, "and come down
swiftly with the slope of the ground in my favor. They will not
throw themselves upon my back in their hundreds, but--"

"I know it," said Bagheera. "Would that Baloo were here, but
we must do what we can. When that cloud covers the moon I shall
go to the terrace. They hold some sort of council there over the
boy."

"Good hunting," said Kaa grimly, and glided away to the west
wall. That happened to be the least ruined of any, and the big
snake was delayed awhile before he could find a way up the stones.
The cloud hid the moon, and as Mowgli wondered what would come
next he heard Bagheera's light feet on the terrace. The Black
Panther had raced up the slope almost without a sound and was
striking--he knew better than to waste time in biting--right
and left among the monkeys, who were seated round Mowgli in
circles fifty and sixty deep. There was a howl of fright and
rage, and then as Bagheera tripped on the rolling kicking bodies
beneath him, a monkey shouted: "There is only one here! Kill him!
Kill." A scuffling mass of monkeys, biting, scratching, tearing,
and pulling, closed over Bagheera, while five or six laid hold of
Mowgli, dragged him up the wall of the summerhouse and pushed him
through the hole of the broken dome. A man-trained boy would have
been badly bruised, for the fall was a good fifteen feet, but
Mowgli fell as Baloo had taught him to fall, and landed on his
feet.

"Stay there," shouted the monkeys, "till we have killed thy
friends, and later we will play with thee--if the Poison-People
leave thee alive."

"We be of one blood, ye and I," said Mowgli, quickly giving
the Snake's Call. He could hear rustling and hissing in the
rubbish all round him and gave the Call a second time, to make
sure.

"Even ssso! Down hoods all!" said half a dozen low voices
(every ruin in India becomes sooner or later a dwelling place of
snakes, and the old summerhouse was alive with cobras). "Stand
still, Little Brother, for thy feet may do us harm."

Mowgli stood as quietly as he could, peering through the open
work and listening to the furious din of the fight round the Black
Panther--the yells and chatterings and scufflings, and
Bagheera's deep, hoarse cough as he backed and bucked and twisted
and plunged under the heaps of his enemies. For the first time
since he was born, Bagheera was fighting for his life.

"Baloo must be at hand; Bagheera would not have come alone,"
Mowgli thought. And then he called aloud: "To the tank, Bagheera.
Roll to the water tanks. Roll and plunge! Get to the water!"

Bagheera heard, and the cry that told him Mowgli was safe gave
him new courage. He worked his way desperately, inch by inch,
straight for the reservoirs, halting in silence. Then from the
ruined wall nearest the jungle rose up the rumbling war-shout of
Baloo. The old Bear had done his best, but he could not come
before. "Bagheera," he shouted, "I am here. I climb! I haste!
Ahuwora! The stones slip under my feet! Wait my coming, O most
infamous Bandar-log!" He panted up the terrace only to disappear
to the head in a wave of monkeys, but he threw himself squarely on
his haunches, and, spreading out his forepaws, hugged as many as
he could hold, and then began to hit with a regular bat-bat-bat,
like the flipping strokes of a paddle wheel. A crash and a splash
told Mowgli that Bagheera had fought his way to the tank where the
monkeys could not follow. The Panther lay gasping for breath, his
head just out of the water, while the monkeys stood three deep on
the red steps, dancing up and down with rage, ready to spring upon
him from all sides if he came out to help Baloo. It was then that
Bagheera lifted up his dripping chin, and in despair gave the
Snake's Call for protection--"We be of one blood, ye and I"--
for he believed that Kaa had turned tail at the last minute. Even
Baloo, half smothered under the monkeys on the edge of the
terrace, could not help chuckling as he heard the Black Panther
asking for help.

Kaa had only just worked his way over the west wall, landing
with a wrench that dislodged a coping stone into the ditch. He
had no intention of losing any advantage of the ground, and coiled
and uncoiled himself once or twice, to be sure that every foot of
his long body was in working order. All that while the fight with
Baloo went on, and the monkeys yelled in the tank round Bagheera,
and Mang the Bat, flying to and fro, carried the news of the great
battle over the jungle, till even Hathi the Wild Elephant
trumpeted, and, far away, scattered bands of the Monkey-Folk woke
and came leaping along the tree-roads to help their comrades in
the Cold Lairs, and the noise of the fight roused all the day
birds for miles round. Then Kaa came straight, quickly, and
anxious to kill. The fighting strength of a python is in the
driving blow of his head backed by all the strength and weight of
his body. If you can imagine a lance, or a battering ram, or a
hammer weighing nearly half a ton driven by a cool, quiet mind
living in the handle of it, you can roughly imagine what Kaa was
like when he fought. A python four or five feet long can knock a
man down if he hits him fairly in the chest, and Kaa was thirty
feet long, as you know. His first stroke was delivered into the
heart of the crowd round Baloo. It was sent home with shut mouth
in silence, and there was no need of a second. The monkeys
scattered with cries of--"Kaa! It is Kaa! Run! Run!"

Generations of monkeys had been scared into good behavior by
the stories their elders told them of Kaa, the night thief, who
could slip along the branches as quietly as moss grows, and steal
away the strongest monkey that ever lived; of old Kaa, who could
make himself look so like a dead branch or a rotten stump that the
wisest were deceived, till the branch caught them. Kaa was
everything that the monkeys feared in the jungle, for none of them
knew the limits of his power, none of them could look him in the
face, and none had ever come alive out of his hug. And so they
ran, stammering with terror, to the walls and the roofs of the
houses, and Baloo drew a deep breath of relief. His fur was much
thicker than Bagheera's, but he had suffered sorely in the fight.
Then Kaa opened his mouth for the first time and spoke one long
hissing word, and the far-away monkeys, hurrying to the defense of
the Cold Lairs, stayed where they were, cowering, till the loaded
branches bent and crackled under them. The monkeys on the walls
and the empty houses stopped their cries, and in the stillness
that fell upon the city Mowgli heard Bagheera shaking his wet
sides as he came up from the tank. Then the clamor broke out
again. The monkeys leaped higher up the walls. They clung around
the necks of the big stone idols and shrieked as they skipped
along the battlements, while Mowgli, dancing in the summerhouse,
put his eye to the screenwork and hooted owl-fashion between his
front teeth, to show his derision and contempt.

"Get the man-cub out of that trap; I can do no more," Bagheera
gasped. "Let us take the man-cub and go. They may attack again."

"They will not move till I order them. Stay you sssso!" Kaa
hissed, and the city was silent once more. "I could not come
before, Brother, but I think I heard thee call"--this was to
Bagheera.

"I--I may have cried out in the battle," Bagheera answered.
"Baloo, art thou hurt?

"I am not sure that they did not pull me into a hundred little
bearlings," said Baloo, gravely shaking one leg after the other.
"Wow! I am sore. Kaa, we owe thee, I think, our lives--Bagheera
and I."

"No matter. Where is the manling?"

"Here, in a trap. I cannot climb out," cried Mowgli. The
curve of the broken dome was above his head.

"Take him away. He dances like Mao the Peacock. He will
crush our young," said the cobras inside.

"Hah!" said Kaa with a chuckle, "he has friends everywhere,
this manling. Stand back, manling. And hide you, O Poison
People. I break down the wall."

Kaa looked carefully till he found a discolored crack in the
marble tracery showing a weak spot, made two or three light taps
with his head to get the distance, and then lifting up six feet of
his body clear of the ground, sent home half a dozen full-power
smashing blows, nose-first. The screen-work broke and fell away
in a cloud of dust and rubbish, and Mowgli leaped through the
opening and flung himself between Baloo and Bagheera--an arm
around each big neck.

"Art thou hurt?" said Baloo, hugging him softly.

"I am sore, hungry, and not a little bruised. But, oh, they
have handled ye grievously, my Brothers! Ye bleed."

"Others also," said Bagheera, licking his lips and looking at
the monkey-dead on the terrace and round the tank.

"It is nothing, it is nothing, if thou art safe, oh, my pride
of all little frogs!" whimpered Baloo.

"Of that we shall judge later," said Bagheera, in a dry voice
that Mowgli did not at all like. "But here is Kaa to whom we owe
the battle and thou owest thy life. Thank him according to our
customs, Mowgli."

Mowgli turned and saw the great Python's head swaying a foot
above his own.

"So this is the manling," said Kaa. "Very soft is his skin,
and he is not unlike the Bandar-log. Have a care, manling, that I
do not mistake thee for a monkey some twilight when I have newly
changed my coat."

"We be one blood, thou and I," Mowgli answered. "I take my
life from thee tonight. My kill shall be thy kill if ever thou
art hungry, O Kaa."

"All thanks, Little Brother," said Kaa, though his eyes
twinkled. "And what may so bold a hunter kill? I ask that I may
follow when next he goes abroad."

"I kill nothing,--I am too little,--but I drive goats
toward such as can use them. When thou art empty come to me and
see if I speak the truth. I have some skill in these [he held out
his hands], and if ever thou art in a trap, I may pay the debt
which I owe to thee, to Bagheera, and to Baloo, here. Good
hunting to ye all, my masters."

"Well said," growled Baloo, for Mowgli had returned thanks
very prettily. The Python dropped his head lightly for a minute
on Mowgli's shoulder. "A brave heart and a courteous tongue,"
said he. "They shall carry thee far through the jungle, manling.
But now go hence quickly with thy friends. Go and sleep, for the
moon sets, and what follows it is not well that thou shouldst
see."

The moon was sinking behind the hills and the lines of
trembling monkeys huddled together on the walls and battlements
looked like ragged shaky fringes of things. Baloo went down to
the tank for a drink and Bagheera began to put his fur in order,
as Kaa glided out into the center of the terrace and brought his
jaws together with a ringing snap that drew all the monkeys' eyes
upon him.

"The moon sets," he said. "Is there yet light enough to see?"

From the walls came a moan like the wind in the tree-tops--
"We see, O Kaa."

"Good. Begins now the dance--the Dance of the Hunger of
Kaa. Sit still and watch."

He turned twice or thrice in a big circle, weaving his head
from right to left. Then he began making loops and figures of
eight with his body, and soft, oozy triangles that melted into
squares and five-sided figures, and coiled mounds, never resting,
never hurrying, and never stopping his low humming song. It grew
darker and darker, till at last the dragging, shifting coils
disappeared, but they could hear the rustle of the scales.

Baloo and Bagheera stood still as stone, growling in their
throats, their neck hair bristling, and Mowgli watched and
wondered.

"Bandar-log," said the voice of Kaa at last, "can ye stir foot
or hand without my order? Speak!"

"Without thy order we cannot stir foot or hand, O Kaa!"

"Good! Come all one pace nearer to me."

The lines of the monkeys swayed forward helplessly, and Baloo
and Bagheera took one stiff step forward with them.

"Nearer!" hissed Kaa, and they all moved again.

Mowgli laid his hands on Baloo and Bagheera to get them away,
and the two great beasts started as though they had been waked
from a dream.

"Keep thy hand on my shoulder," Bagheera whispered. "Keep it
there, or I must go back--must go back to Kaa. Aah!"

"It is only old Kaa making circles on the dust," said Mowgli.
"Let us go." And the three slipped off through a gap in the walls
to the jungle.

"Whoof!" said Baloo, when he stood under the still trees
again. "Never more will I make an ally of Kaa," and he shook
himself all over.

"He knows more than we," said Bagheera, trembling. "In a
little time, had I stayed, I should have walked down his throat."

"Many will walk by that road before the moon rises again,"
said Baloo. "He will have good hunting--after his own fashion."

"But what was the meaning of it all?" said Mowgli, who did not
know anything of a python's powers of fascination. "I saw no more
than a big snake making foolish circles till the dark came. And
his nose was all sore. Ho! Ho!"

"Mowgli," said Bagheera angrily, "his nose was sore on thy
account, as my ears and sides and paws, and Baloo's neck and
shoulders are bitten on thy account. Neither Baloo nor Bagheera
will be able to hunt with pleasure for many days."

"It is nothing," said Baloo; "we have the man-cub again."

"True, but he has cost us heavily in time which might have
been spent in good hunting, in wounds, in hair--I am half
plucked along my back--and last of all, in honor. For,
remember, Mowgli, I, who am the Black Panther, was forced to call
upon Kaa for protection, and Baloo and I were both made stupid as
little birds by the Hunger Dance. All this, man-cub, came of thy
playing with the Bandar-log."

"True, it is true," said Mowgli sorrowfully. "I am an evil
man-cub, and my stomach is sad in me."

"Mf! What says the Law of the Jungle, Baloo?"

Baloo did not wish to bring Mowgli into any more trouble, but
he could not tamper with the Law, so he mumbled: "Sorrow never
stays punishment. But remember, Bagheera, he is very little."

"I will remember. But he has done mischief, and blows must be
dealt now. Mowgli, hast thou anything to say?"

"Nothing. I did wrong. Baloo and thou are wounded. It is
just."

Bagheera gave him half a dozen love-taps from a panther's
point of view (they would hardly have waked one of his own cubs),
but for a seven-year-old boy they amounted to as severe a beating
as you could wish to avoid. When it was all over Mowgli sneezed,
and picked himself up without a word.

"Now," said Bagheera, "jump on my back, Little Brother, and we
will go home."

One of the beauties of Jungle Law is that punishment settles
all scores. There is no nagging afterward.

Mowgli laid his head down on Bagheera's back and slept so
deeply that he never waked when he was put down in the home-cave.


Road-Song of the Bandar-Log

Here we go in a flung festoon,
Half-way up to the jealous moon!
Don't you envy our pranceful bands?
Don't you wish you had extra hands?
Wouldn't you like if your tails were--so--
Curved in the shape of a Cupid's bow?
Now you're angry, but--never mind,
Brother, thy tail hangs down behind!

Here we sit in a branchy row,
Thinking of beautiful things we know;
Dreaming of deeds that we mean to do,
All complete, in a minute or two--
Something noble and wise and good,
Done by merely wishing we could.
We've forgotten, but--never mind,
Brother, thy tail hangs down behind!

All the talk we ever have heard
Uttered by bat or beast or bird--
Hide or fin or scale or feather--
Jabber it quickly and all together!
Excellent! Wonderful! Once again!

Now we are talking just like men!
Let's pretend we are ... never mind,
Brother, thy tail hangs down behind!
This is the way of the Monkey-kind.

Then join our leaping lines that scumfish through the pines,
That rocket by where, light and high, the wild grape swings.
By the rubbish in our wake, and the noble noise we make,
Be sure, be sure, we're going to do some splendid things!


"Tiger! Tiger!"

What of the hunting, hunter bold?
Brother, the watch was long and cold.
What of the quarry ye went to kill?
Brother, he crops in the jungle still.
Where is the power that made your pride?
Brother, it ebbs from my flank and side.
Where is the haste that ye hurry by?
Brother, I go to my lair--to die.

Now we must go back to the first tale. When Mowgli left the
wolf's cave after the fight with the Pack at the Council Rock, he
went down to the plowed lands where the villagers lived, but he
would not stop there because it was too near to the jungle, and he
knew that he had made at least one bad enemy at the Council. So
he hurried on, keeping to the rough road that ran down the valley,
and followed it at a steady jog-trot for nearly twenty miles, till
he came to a country that he did not know. The valley opened out
into a great plain dotted over with rocks and cut up by ravines.
At one end stood a little village, and at the other the thick
jungle came down in a sweep to the grazing-grounds, and stopped
there as though it had been cut off with a hoe. All over the
plain, cattle and buffaloes were grazing, and when the little boys
in charge of the herds saw Mowgli they shouted and ran away, and
the yellow pariah dogs that hang about every Indian village
barked. Mowgli walked on, for he was feeling hungry, and when he
came to the village gate he saw the big thorn-bush that was drawn
up before the gate at twilight, pushed to one side.

"Umph!" he said, for he had come across more than one such
barricade in his night rambles after things to eat. "So men are
afraid of the People of the Jungle here also." He sat down by the
gate, and when a man came out he stood up, opened his mouth, and
pointed down it to show that he wanted food. The man stared, and
ran back up the one street of the village shouting for the priest,
who was a big, fat man dressed in white, with a red and yellow
mark on his forehead. The priest came to the gate, and with him
at least a hundred people, who stared and talked and shouted and
pointed at Mowgli.
EOD;
    }
    
    protected function rOccupation()
    {
        $list = array('', "Member of Parliament", "Local Government Legislator", "Chief Executive - Central Government", "Chief Executive - Local Government", "Diplomatic Representative", "Chief Executive and/or Managing Director", "Special-Interest Organisation Administrator", "General Manager", "Senior Education Manager", "Broadcasting and Theatrical Production Manager", "Production Manager (Manufacturing)", "Transport Manager", "Forest Manager", "Quarry Manager", "Construction Manager", "Engineering Technical Manager", "Health Services Manager", "Administration Manager", "Property Manager", "Finance Manager", "Human Resources Manager", "Sales and/or Marketing Manager", "Advertising and Public Relations Manager", "Supply and Distribution Manager", "Wholesale and Warehouse Manager", "Retail Manager", "Hotel or Motel Manager", "Restaurant or Tavern Manager", "Other Lodging Services Manager", "Other Catering Services Manager", "Information Technology Manager", "Research and Development Manager", "Quality Assurance Manager", "Office Manager", "Physicist", "Meteorologist", "Chemist (Other than Pharmacist)", "Geologist", "Geophysicist", "Mathematician and/or Statistician", "Systems Analyst", "Computer Applications Engineer", "Systems Manager", "Architect", "Resource Management Planner", "Landscape Architect", "Roading Engineer", "Water Resources Engineer", "Public Health Engineer", "Structural Engineer", "Other Civil Engineer", "Electrical Engineer", "Electronic and Telecommunications Engineer", "Heating, Ventilation and Refrigeration Engineer", "Naval Architect and/or Ships' Surveyor", "Aeronautical Engineer and/or Aircraft Surveyor", "Agricultural Engineer", "Other Mechanical Engineer", "Chemical Engineer", "Metallurgist", "Mining Engineer", "Surveyor", "Cartographer and Photogrammetrist", "Biologist", "Botanist", "Zoologist", "Agronomist", "Horticultural Scientist", "Forestry Scientist", "Environmental Scientist", "Biochemist", "Microbiologist", "Medical Pathologist", "Soil Scientist", "Agricultural Consultant", "Conservation Officer", "Horticultural Consultant", "Land Management Officer", "General Practitioner", "Resident Medical Officer", "Surgeon", "Physician", "Gynaecologist and Obstetrician", "Radiologist, Radiation Oncologist", "Anaesthetist", "Dentist and Dental Surgeon", "Veterinarian", "Hospital Pharmacist", "Retail Pharmacist", "Dietician and Public Health Nutritionist", "Optometrist", "Principal Nurse", "Registered Nurse", "Psychiatric Nurse", "Plunket Nurse", "Public Health and District Nurse", "Occupational Health Nurse", "Midwife", "University and Higher Education Lecturer and/or Tutor", "Secondary School Teacher", "Primary School Teacher", "Early Childhood Teacher", "Kohanga Reo Teacher", "Special Education Teacher", "Speech-Language Therapist", "Teacher of English to Speakers of Other Languages", "Education Adviser", "Education Reviewer", "Accountant", "Auditor", "Human Resources Officer", "Training and Development Officer", "Market Research Analyst", "Public Relations Officer", "Financial Adviser", "Fundraiser", "Management Consultant", "Barrister and Solicitor", "Judge", "Other Legal Professional", "Archivist", "Art Gallery and/or Museum Curator", "Librarian", "Information Services Administrator", "Economist", "Social Scientist", "Policy Analyst", "Philologist, Translator or Interpreter", "Psychologist", "Psychotherapist", "Counsellor", "Diplomatic Official", "Minister of Religion", "Physical Science Technician", "Quantity Surveyor", "Surveyor's Technician", "Clerk of Works", "Other Civil Engineering Technician", "Electrical Engineering Technician", "Telecommunications Technician", "Computer Systems Technician", "Other Electronics Engineering Technician", "Avionics Technician", "Mechanical Engineering Technician", "Chemical Engineering Technician", "Draughting Technician", "Other Engineering Technician", "Non Destructive Testing Technician", "Computer Programmer", "Computer Operator", "Computer Support Technician", "Photographer", "Camera Operator", "Sound Recording Equipment Controller", "Broadcasting Transmitting and Studio Equipment Operator", "Radio Operator", "Cinema Projectionist", "Medical Radiation Technologist", "Other Medical Equipment Controller", "Sonographer", "Ships' Engineer", "Ships' Officer (Deck) Including Master", "Launch Master", "Other Ships' Deck Officer and Pilot", "Aircraft Pilot and Flight Crew", "Flying Instructor", "Helicopter Pilot", "Air Traffic Controller", "Safety Inspector", "Meat Inspector", "Noxious Weeds/Pest Inspector", "Health Inspector", "Agricultural Inspector", "Quality Inspector", "Life Science Technician", "Medical Laboratory Technician", "Agricultural Technician", "Forest Technician", "Dispensing Optician", "Dental Therapist", "Physiotherapist", "Occupational Therapist", "Osteopath", "Orthotist and/or Prosthetist", "Podiatrist", "Chiropractor", "Veterinary Assistant", "Hospital Dispensary Assistant", "Retail Dispensary Assistant", "Other Health Associate Professional", "Dental Technician", "Enrolled Nurse", "Karitane Nurse", "Financial Dealer and Broker", "Insurance Representative", "Real Estate Agent/Property Consultant", "Property Developer", "Travel Consultant", "Business Services Representative", "Technical Representative", "Sales Representative", "Wholesale and/or Retail Buyer", "Livestock Buyer", "Purchasing Agent", "Wool Buyer/Merchant", "Valuer", "Auctioneer", "Stock and Station Agent", "Administration Officer", "Conference/Function Organiser", "Legal Executive", "Legal Clerk", "Bookkeeper", "Organisation and Methods Analyst", "Building Control/Consents Officer", "Customs Officer", "Quarantine and Agriculture Ports Officer", "Immigration Officer", "Social Worker", "Probation Worker", "Case Worker", "Employment Programme Teaching Associate Professional", "Teacher Aide", "Careers, Transition, Employment Adviser", "Author and Critic", "Reporter", "Editor", "Sub-Editor", "Copywriter", "Sculptor, Painter and Related Artist", "Graphic Designer", "Fashion Designer", "Display and Window Dresser", "Industrial Designer", "Paste Up Artist", "Interior Designer", "Composer, Arranger and/or Conductor", "Instrumentalist", "Singer", "Singing and Music Teacher", "Dancer", "Dancing Teacher and/or Choreographer", "Actor", "Artistic Director", "Radio and Television Presenter", "Clown, Magician, Acrobat and Related Worker", "Professional Sportsperson", "Sports Coach or Trainer", "Sports Official", "Non-Ordained Religious Assistant", "Acclimatisation Field Officer", "National Park Ranger", "Typist and Word Processor Operator", "Data Entry Operator", "Secretary", "Accounts Clerk", "Audit Clerk", "Costing Clerk", "Finance Clerk", "Statistical Clerk", "Survey Interviewer", "Stock Clerk", "Dispatch and Receiving Clerk", "Weighing and Tally Clerk", "Material and Production Planning Clerk", "Transport Clerk", "Library Assistant", "Record and Filing Clerk", "Mail Sorting Clerk", "Mail Clerk", "Postal Deliverer", "Mail Delivery Contractor", "Proof Reader", "General Clerk", "Office Machine Operator", "Human Resources Clerk", "Cashier", "Checkout Operator", "Ticket-Seller", "Bank Officer", "Gaming Dealer", "Bill and Debt Collector", "Hotel and/or Motel Receptionist", "Patient Receptionist", "Information Clerk and Other Receptionist", "Telephone Switchboard Operator", "Travel Attendant", "Tour and Travel Guide", "Outdoor Recreation Guide", "Housekeeper (Private Service)", "Housekeeper (Not Private)", "Chef", "Cook", "Bartender", "Wine Waiter", "Waiter", "Catering Counter Assistant", "Kitchenhand", "Usher and Cloakroom Attendant", "Hospital Orderly", "Health Assistant", "Ambulance Officer", "Nurse Aide", "Care Giver", "Hairdresser", "Beauty Therapist", "Massage Therapist", "Weight Loss Consultant", "Child Care Worker", "Funeral Director", "Fire Fighter", "Detective", "Police Officer", "Prison Officer", "Private Investigator", "Security Officer", "Armed Forces", "Sales Assistant", "Demonstrator", "Forecourt Attendant", "Street Vendor and Related Worker", "Fashion and Other Model", "Field Crop Grower and Related Worker", "Market Gardener and Related Worker", "Fruit Grower, Worker", "Grape Grower and/or Wine Maker, Worker", "Nursery Grower, Nursery Worker", "Landscape Gardener", "Grounds or Green Keeper", "Gardener", "Dairy Farmer, Dairy Farm Worker", "Sheep Farmer, Sheep Farm Worker", "Cattle Farmer, Cattle Farm Worker", "Pig Farmer, Pig Farm Worker", "Goat Farmer, Goat Farm Worker", "Deer Farmer, Deer Farm Worker", "Stud Race-Horse Breeder, Stud Worker", "Other Livestock Farmer, Other Livestock Farm Worker", "Mixed Livestock Farmer, Mixed Livestock Farm Worker", "Poultry Farmer and Poultry Farm Worker", "Apiarist and Apiary Worker", "Crop and Livestock Farmer, Worker", "Shepherd or Musterer", "Shearing Contractor/Shearer", "Wool Classer", "Shearing Shed Hand", "Horse Trainer, Groom or Stable Hand", "Sampling Officer", "Logger", "Forest Hand", "Forestry Contractor", "Fishing Skipper, Fisherperson", "Shell Fisher", "Fish Farmer, Worker", "Mussel and Oyster Farmer, Worker", "Hunter and Trapper", "Animal Welfare Worker", "Bricklayer and/or Blocklayer", "Stonemason", "Carpenter and/or Joiner", "Builder (Including Contractor)", "Boatbuilder", "Plasterer", "Glazier", "Plumber", "Painter, Decorator and/or Paperhanger", "Spray Painter", "Sign Writer", "Electrician", "Transport Electrician", "Appliance Electrician", "Metal Mould Maker", "Coach Builder", "Sheet-Metal Worker", "Boiler Maker", "Fitter and Welder", "Panel Beater", "Blacksmith", "Pattern Maker", "Tool and/or Die Maker", "Fitter and Turner", "Saw Doctor", "Machinery Mechanic", "Motor Mechanic", "Aircraft Engine Mechanic", "Heating, Ventilation and Refrigeration Mechanic", "Small Engine Mechanic", "Mechanical Products Inspector and Tester", "Electrical Fitter", "Fire Alarm Technician", "Electronics Serviceperson", "Avionics Mechanic", "Radio and Television Repairer", "Industrial Precision Instrument Maker and Repairer", "Locksmith", "Optical Instrument Maker, Repairer and Mechanic", "Watchmaker and Repairer", "Musical Instrument Maker, Repairer and Tuner", "Jeweller and Jewellery Repairer", "Gem Cutter and Polisher", "Glass Cutter and Beveller", "Graphic Pre-Press Tradesperson", "Screen Printer", "Printing Machinist", "Desktop Publisher", "Bookbinder", "Photolithographer, Photo Engraver", "Butcher", "Meat Grader", "Baker", "Cabinet Maker", "Furniture Finisher", "Tailor/Dressmaker", "Textile Products Pattern Maker", "Textile Products Marker and Cutter", "Furniture Upholsterer", "Vehicle Upholsterer and Trimmer", "Canvas Worker", "Carpet and Other Floor Covering Layer", "Saddler and Harness Maker", "Shoe Repairer", "Quarry and Mine Worker", "Mining Plant Operator", "Mineral and Stone Treater", "Driller", "Metallic Furnace Operator", "Drop Hammer and Forging Press Operator", "Metal Caster", "Welder and Flame-Cutter", "Metal Drawer and/or Extruder", "Non-Metallic Mineral Products Kiln or Furnace Operator", "Clay Product Plant Operator", "Pottery and Porcelain Mould Maker", "Glass Pressing and Drawing Machine Operator", "Glass and Ceramics Painter and Decorator", "Timber Processing Machine Operator", "Timber Grader, Classer", "Pulp Production Worker", "Paper Production Worker", "Chemical Crushing, Grinding and Mixing Operator", "Filtering and Separating Equipment Operator", "Water Treatment Plant Operator", "Still and Reactor Operator", "Other Chemical Processing Plant Operator", "Power Generating Plant Operator", "Boiler Attendant", "Pumping-Station Operator", "Other Stationary Engine Operator", "Machine Tool Operator", "Automated Machine Operator", "Spring Maker and Wire Worker", "Tool Grinder and Sharpener", "Power Shear Operator", "Concrete Worker", "Pharmaceutical and Toiletry Products Machine Operator", "Electroplater", "Metal Polisher", "Photographic Darkroom Operator", "Tyre Moulder and Builder", "Tyre Retreader", "Rubber Machine Operator", "Plastics Machine Operator", "Plastics Laminator", "Woodworking Machinist", "Wood Panel Production Worker", "Joiner's Benchhand", "Preservation Plant Operator", "Wood Seasoning Kiln Operator", "Paper Products Machine Operator", "Cardboard Forme Maker and Finisher", "Guillotine Operator", "Spinner and Winder", "Cloth Weaver", "Carpet Weaver", "Knitter, Knitting Machinist", "Sewing Machinist", "Embroiderer", "Stuffed Toy Maker", "Hat Maker", "Launderer", "Bleacher and Dyer", "Dry-Cleaner", "Presser", "Textile Finisher", "Carpet Cleaner", "Fibre Preparer", "Wool Scourer", "Slaughterer", "Smallgoods Maker", "Oyster Opener and Canner", "Meat Processing Worker", "Fish Processing Worker", "Milk and Other Dairy Products Maker", "Cheese Maker", "Grain Miller", "Baked Goods and Cereals Producing Machine Operator", "Baker's Assistant", "Fruit, Vegetable and Nut Processing Machine Operator", "Sugar Processor and Refiner", "Confectionery Maker", "Other Food Products Processing Machine Operator", "Tobacco Product Process Worker", "Brewery Worker", "Distillery Worker (Alcoholic Beverages)", "Wine Making Machine Operator", "Hide and Pelt Processor", "Tanner, Splitter and Dyer", "Machinery Assembler", "Coil Winder", "Electric and Electronic Equipment Assembler", "Linesperson", "Electric Cable Jointer", "Metal Goods Assembler", "Plastic and Rubber Goods Assembler", "Wood and Related Materials Products Assembler", "Basket and Wicker Worker", "Fencer", "Leather Goods Assembler", "Footwear Production Machine Operator", "Railway Locomotive Driver", "Taxi Driver", "Light Truck or Van Driver", "Driving Instructor", "Passenger Coach Driver", "Heavy Truck or Tanker Driver", "Farm Machinery Operator, Including Contractor", "Ground Spraying and/or Dusting Contractor", "Excavating Machine Operator", "Pile Driver, Driller Operator", "Earthmoving Machine Operator", "Roading and/or Paving Machine Operator", "Crane Operator", "Fork-Lift Operator", "Straddle-Truck Operator", "Tow Truck Operator", "Deck Rating", "Other Ship or Boat Hand", "Building Exterior Cleaner", "Drainlayer", "Pipe Fitter", "Steel Fixer", "Scaffolder", "Rigger and Cable Splicer", "Steel Erector, Construction", "Roofer", "Aluminium Joiner", "Insulator", "Underwater Worker", "Cleaner", "Building Caretaker", "Pest Control Worker", "Courier and Deliverer", "Hotel Porter", "Refuse Collector", "Street or Park Cleaner", "Packer", "Loader and/or Checker", "Railway Shunter", "Surveyor's Assistant", "Builder's Labourer", "Sawmill Labourer", "General Labourer");
        return $list[rand(0, count($list)-1)];
    }
    
    protected function rCompany()
    {
        $company = ucwords($this->rWords(2));
        return $company .  ' Ltd.';
    }
    
    protected function townCountys()
    {
        return array(
            array("Ampthill","Bedfordshire","England"),
            array("Arlesey","Bedfordshire","England"),
            array("Bedford","Bedfordshire","England"),
            array("Biggleswade","Bedfordshire","England"),
            array("Dunstable","Bedfordshire","England"),
            array("Flitwick","Bedfordshire","England"),
            array("Houghton Regis","Bedfordshire","England"),
            array("Kempston","Bedfordshire","England"),
            array("Leighton Buzzard","Bedfordshire","England"),
            array("Linslade","Bedfordshire","England"),
            array("Luton","Bedfordshire","England"),
            array("Potton","Bedfordshire","England"),
            array("Sandy","Bedfordshire","England"),
            array("Shefford","Bedfordshire","England"),
            array("Stotfold","Bedfordshire","England"),
            array("Wixams","Bedfordshire","England"),
            array("Woburn","Bedfordshire","England"),
            array("Ascot","Berkshire","England"),
            array("Bracknell","Berkshire","England"),
            array("Crowthorne","Berkshire","England"),
            array("Earley","Berkshire","England"),
            array("Eton","Berkshire","England"),
            array("Hungerford","Berkshire","England"),
            array("Lambourn","Berkshire","England"),
            array("Maidenhead","Berkshire","England"),
            array("Newbury","Berkshire","England"),
            array("Reading","Berkshire","England"),
            array("Sandhurst","Berkshire","England"),
            array("Slough","Berkshire","England"),
            array("Thatcham","Berkshire","England"),
            array("Windsor","Berkshire","England"),
            array("Wokingham","Berkshire","England"),
            array("Woodley","Berkshire","England"),
            array("Bristol","Bristol","England"),
            array("Amersham","Buckinghamshire","England"),
            array("Aylesbury","Buckinghamshire","England"),
            array("Beaconsfield","Buckinghamshire","England"),
            array("Bletchley","Buckinghamshire","England"),
            array("Buckingham","Buckinghamshire","England"),
            array("Chesham","Buckinghamshire","England"),
            array("Fenny Stratford","Buckinghamshire","England"),
            array("High Wycombe","Buckinghamshire","England"),
            array("Marlow","Buckinghamshire","England"),
            array("Milton Keynes","Buckinghamshire","England"),
            array("Newport Pagnell","Buckinghamshire","England"),
            array("Olney","Buckinghamshire","England"),
            array("Princes Risborough","Buckinghamshire","England"),
            array("Stony Stratford","Buckinghamshire","England"),
            array("Wendover","Buckinghamshire","England"),
            array("Winslow","Buckinghamshire","England"),
            array("Woburn Sands","Buckinghamshire","England"),
            array("Wolverton and Greenleys","Buckinghamshire","England"),
            array("Cambridge","Cambridgeshire","England"),
            array("Chatteris","Cambridgeshire","England"),
            array("Ely","Cambridgeshire","England"),
            array("Fulbourn","Cambridgeshire","England"),
            array("Godmanchester","Cambridgeshire","England"),
            array("Hanley Grange","Cambridgeshire","England"),
            array("Huntingdon","Cambridgeshire","England"),
            array("March","Cambridgeshire","England"),
            array("Northstowe","Cambridgeshire","England"),
            array("Peterborough","Cambridgeshire","England"),
            array("Ramsey","Cambridgeshire","England"),
            array("Soham","Cambridgeshire","England"),
            array("St Ives","Cambridgeshire","England"),
            array("St Neots","Cambridgeshire","England"),
            array("Whittlesey","Cambridgeshire","England"),
            array("Wisbech","Cambridgeshire","England"),
            array("Alsager","Cheshire","England"),
            array("Birchwood","Cheshire","England"),
            array("Bollington","Cheshire","England"),
            array("Chester","Cheshire","England"),
            array("Congleton","Cheshire","England"),
            array("Crewe","Cheshire","England"),
            array("Ellesmere Port","Cheshire","England"),
            array("Frodsham","Cheshire","England"),
            array("Knutsford","Cheshire","England"),
            array("Macclesfield","Cheshire","England"),
            array("Malpas","Cheshire","England"),
            array("Middlewich","Cheshire","England"),
            array("Nantwich","Cheshire","England"),
            array("Neston","Cheshire","England"),
            array("Northwich","Cheshire","England"),
            array("Poynton with Worth","Cheshire","England"),
            array("Runcorn","Cheshire","England"),
            array("Sandbach","Cheshire","England"),
            array("Warrington","Cheshire","England"),
            array("Widnes","Cheshire","England"),
            array("Wilmslow","Cheshire","England"),
            array("Winsford","Cheshire","England"),
            array("Bodmin","Cornwall","England"),
            array("Bude","Cornwall","England"),
            array("Callington","Cornwall","England"),
            array("Camborne","Cornwall","England"),
            array("Camelford","Cornwall","England"),
            array("Charlestown","Cornwall","England"),
            array("Falmouth","Cornwall","England"),
            array("Fowey","Cornwall","England"),
            array("Hayle","Cornwall","England"),
            array("Helston","Cornwall","England"),
            array("Launceston","Cornwall","England"),
            array("Liskeard","Cornwall","England"),
            array("Looe","Cornwall","England"),
            array("Lostwithiel","Cornwall","England"),
            array("Marazion","Cornwall","England"),
            array("Newlyn","Cornwall","England"),
            array("Newquay","Cornwall","England"),
            array("Padstow","Cornwall","England"),
            array("Par","Cornwall","England"),
            array("Penryn","Cornwall","England"),
            array("Penzance","Cornwall","England"),
            array("Porthleven","Cornwall","England"),
            array("Redruth","Cornwall","England"),
            array("Saltash","Cornwall","England"),
            array("St Austell","Cornwall","England"),
            array("St Blazey","Cornwall","England"),
            array("St Columb Major","Cornwall","England"),
            array("St Ives","Cornwall","England"),
            array("St Just","Cornwall","England"),
            array("St Mawes","Cornwall","England"),
            array("Stratton","Cornwall","England"),
            array("Torpoint","Cornwall","England"),
            array("Truro","Cornwall","England"),
            array("Wadebridge","Cornwall","England"),
            array("Barnard Castle","County Durham","England"),
            array("Billingham","County Durham","England"),
            array("Bishop Auckland","County Durham","England"),
            array("Chester le Street","County Durham","England"),
            array("Consett","County Durham","England"),
            array("Crook","County Durham","England"),
            array("Darlington","County Durham","England"),
            array("Durham","County Durham","England"),
            array("Eaglescliffe","County Durham","England"),
            array("Eastington","County Durham","England"),
            array("Ferryhill","County Durham","England"),
            array("Greater Willington","County Durham","England"),
            array("Hartlepool","County Durham","England"),
            array("Newton Aycliffe","County Durham","England"),
            array("Peterlee","County Durham","England"),
            array("Seaham","County Durham","England"),
            array("Sedgefield","County Durham","England"),
            array("Shildon","County Durham","England"),
            array("Spennymoor","County Durham","England"),
            array("Stanhope","County Durham","England"),
            array("Stanley","County Durham","England"),
            array("Stockton on Tees","County Durham","England"),
            array("Tow Law","County Durham","England"),
            array("Willington","County Durham","England"),
            array("Wolsingham","County Durham","England"),
            array("Alston","Cumbria","England"),
            array("Ambleside","Cumbria","England"),
            array("Appleby in Westmorland","Cumbria","England"),
            array("Aspatria","Cumbria","England"),
            array("Barrow in Furness","Cumbria","England"),
            array("Bowness on Windermere","Cumbria","England"),
            array("Brampton","Cumbria","England"),
            array("Broughton in Furness","Cumbria","England"),
            array("Carlisle","Cumbria","England"),
            array("Cleator Moor","Cumbria","England"),
            array("Cockermouth","Cumbria","England"),
            array("Dalton in Furness","Cumbria","England"),
            array("Egremont","Cumbria","England"),
            array("Grange over Sands","Cumbria","England"),
            array("Harrington","Cumbria","England"),
            array("Kendal","Cumbria","England"),
            array("Keswick","Cumbria","England"),
            array("Kirkby Lonsdale","Cumbria","England"),
            array("Kirkby Stephen","Cumbria","England"),
            array("Longtown","Cumbria","England"),
            array("Maryport","Cumbria","England"),
            array("Millom","Cumbria","England"),
            array("Milnthorpe","Cumbria","England"),
            array("Orgill","Cumbria","England"),
            array("Penrith","Cumbria","England"),
            array("Sedbergh","Cumbria","England"),
            array("Silloth","Cumbria","England"),
            array("Staveley","Cumbria","England"),
            array("Ulverston","Cumbria","England"),
            array("Whitehaven","Cumbria","England"),
            array("Wigton","Cumbria","England"),
            array("Windermere","Cumbria","England"),
            array("Workington","Cumbria","England"),
            array("Alfreton","Derbyshire","England"),
            array("Ashbourne","Derbyshire","England"),
            array("Bakewell","Derbyshire","England"),
            array("Barrow Hill and Whittington","Derbyshire","England"),
            array("Belper","Derbyshire","England"),
            array("Bolsover","Derbyshire","England"),
            array("Buxton","Derbyshire","England"),
            array("Chapel en le Frith","Derbyshire","England"),
            array("Chesterfield","Derbyshire","England"),
            array("Clay Cross","Derbyshire","England"),
            array("Darley Dale","Derbyshire","England"),
            array("Derby","Derbyshire","England"),
            array("Dronfield","Derbyshire","England"),
            array("Dronfield Woodhouse","Derbyshire","England"),
            array("Eckington","Derbyshire","England"),
            array("Fairfield","Derbyshire","England"),
            array("Glossop","Derbyshire","England"),
            array("Hadfield","Derbyshire","England"),
            array("Heanor","Derbyshire","England"),
            array("Ilkeston","Derbyshire","England"),
            array("Killamarsh","Derbyshire","England"),
            array("Langley Mill","Derbyshire","England"),
            array("Long Eaton","Derbyshire","England"),
            array("Matlock","Derbyshire","England"),
            array("Melbourne","Derbyshire","England"),
            array("Netherthorpe","Derbyshire","England"),
            array("New Mills","Derbyshire","England"),
            array("Over Woodhouse","Derbyshire","England"),
            array("Ripley","Derbyshire","England"),
            array("Sandiacre","Derbyshire","England"),
            array("Shallcross","Derbyshire","England"),
            array("Shirebrook","Derbyshire","England"),
            array("Staveley","Derbyshire","England"),
            array("Swadlincote","Derbyshire","England"),
            array("Whaley Bridge","Derbyshire","England"),
            array("Wirksworth","Derbyshire","England"),
            array("Ashburton","Devon","England"),
            array("Axminster","Devon","England"),
            array("Bampton","Devon","England"),
            array("Barnstaple","Devon","England"),
            array("Bideford","Devon","England"),
            array("Bovey Tracey","Devon","England"),
            array("Bradninch","Devon","England"),
            array("Brixham","Devon","England"),
            array("Buckfastleigh","Devon","England"),
            array("Budleigh Salterton","Devon","England"),
            array("Chagford","Devon","England"),
            array("Chudleigh","Devon","England"),
            array("Chulmleigh","Devon","England"),
            array("Colyton","Devon","England"),
            array("Crediton","Devon","England"),
            array("Cullompton","Devon","England"),
            array("Dartmouth","Devon","England"),
            array("Dawlish","Devon","England"),
            array("Exeter","Devon","England"),
            array("Exmouth","Devon","England"),
            array("Great Torrington","Devon","England"),
            array("Hartland","Devon","England"),
            array("Hatherleigh","Devon","England"),
            array("Highampton","Devon","England"),
            array("Holsworthy","Devon","England"),
            array("Honiton","Devon","England"),
            array("Ilfracombe","Devon","England"),
            array("Ivybridge","Devon","England"),
            array("Kingsbridge","Devon","England"),
            array("Kingsteignton","Devon","England"),
            array("Lynton","Devon","England"),
            array("Modbury","Devon","England"),
            array("Moretonhampstead","Devon","England"),
            array("Newton Abbot","Devon","England"),
            array("North Tawton","Devon","England"),
            array("Northam","Devon","England"),
            array("Okehampton","Devon","England"),
            array("Ottery St Mary","Devon","England"),
            array("Paignton","Devon","England"),
            array("Plymouth","Devon","England"),
            array("Princetown","Devon","England"),
            array("Salcombe","Devon","England"),
            array("Seaton","Devon","England"),
            array("Sherford","Devon","England"),
            array("Sidmouth","Devon","England"),
            array("South Molton","Devon","England"),
            array("Tavistock","Devon","England"),
            array("Teignmouth","Devon","England"),
            array("Tiverton","Devon","England"),
            array("Topsham","Devon","England"),
            array("Torquay","Devon","England"),
            array("Totnes","Devon","England"),
            array("Beaminster","Dorset","England"),
            array("Blandford Forum","Dorset","England"),
            array("Bournemouth","Dorset","England"),
            array("Bridport","Dorset","England"),
            array("Chickerell","Dorset","England"),
            array("Christchurch","Dorset","England"),
            array("Dorchester","Dorset","England"),
            array("Ferndown","Dorset","England"),
            array("Gillingham","Dorset","England"),
            array("Highcliffe","Dorset","England"),
            array("Lyme Regis","Dorset","England"),
            array("Poole","Dorset","England"),
            array("Portland","Dorset","England"),
            array("Shaftesbury","Dorset","England"),
            array("Sherborne","Dorset","England"),
            array("Stalbridge","Dorset","England"),
            array("Sturminster Newton","Dorset","England"),
            array("Swanage","Dorset","England"),
            array("Verwood","Dorset","England"),
            array("Wareham","Dorset","England"),
            array("Weymouth","Dorset","England"),
            array("Wimborne Minster","Dorset","England"),
            array("Beverley","East Riding of Yorkshire","England"),
            array("Bridlington","East Riding of Yorkshire","England"),
            array("Brough","East Riding of Yorkshire","England"),
            array("Driffield","East Riding of Yorkshire","England"),
            array("Goole","East Riding of Yorkshire","England"),
            array("Hedon","East Riding of Yorkshire","England"),
            array("Hessle","East Riding of Yorkshire","England"),
            array("Hornsea","East Riding of Yorkshire","England"),
            array("Howden","East Riding of Yorkshire","England"),
            array("Market Weighton","East Riding of Yorkshire","England"),
            array("Pocklington","East Riding of Yorkshire","England"),
            array("Snaith","East Riding of Yorkshire","England"),
            array("South Cave","East Riding of Yorkshire","England"),
            array("Withernsea","East Riding of Yorkshire","England"),
            array("Battle","East Sussex","England"),
            array("Bexhill on Sea","East Sussex","England"),
            array("Brighton","East Sussex","England"),
            array("Crowborough","East Sussex","England"),
            array("Eastbourne","East Sussex","England"),
            array("Hailsham","East Sussex","England"),
            array("Hastings","East Sussex","England"),
            array("Heathfield","East Sussex","England"),
            array("Hove","East Sussex","England"),
            array("Lewes","East Sussex","England"),
            array("Newhaven","East Sussex","England"),
            array("Ore Valley","East Sussex","England"),
            array("Peacehaven","East Sussex","England"),
            array("Polegate","East Sussex","England"),
            array("Rye","East Sussex","England"),
            array("Seaford","East Sussex","England"),
            array("Telscombe","East Sussex","England"),
            array("Uckfield","East Sussex","England"),
            array("Wadhurst","East Sussex","England"),
            array("Winchelsea","East Sussex","England"),
            array("Basildon","Essex","England"),
            array("Billericay","Essex","England"),
            array("Braintree","Essex","England"),
            array("Brentwood","Essex","England"),
            array("Brightlingsea","Essex","England"),
            array("Buckhurst Hill","Essex","England"),
            array("Burnham on Crouch","Essex","England"),
            array("Canvey Island","Essex","England"),
            array("Chafford Hundred","Essex","England"),
            array("Chelmsford","Essex","England"),
            array("Clackwell","Essex","England"),
            array("Clacton on Sea","Essex","England"),
            array("Coggeshall","Essex","England"),
            array("Colchester","Essex","England"),
            array("Corringham","Essex","England"),
            array("Dovercourt","Essex","England"),
            array("Eastwood","Essex","England"),
            array("Epping","Essex","England"),
            array("Frinton on Sea","Essex","England"),
            array("Grays","Essex","England"),
            array("Great Dunmow","Essex","England"),
            array("Hadleigh","Essex","England"),
            array("Halstead","Essex","England"),
            array("Harlow","Essex","England"),
            array("Harwich","Essex","England"),
            array("Heybridge","Essex","England"),
            array("Hockley","Essex","England"),
            array("Holland on Sea","Essex","England"),
            array("Ingatestone","Essex","England"),
            array("Laindon","Essex","England"),
            array("Langdon Hills","Essex","England"),
            array("Leigh on Sea","Essex","England"),
            array("Loughton","Essex","England"),
            array("Maldon","Essex","England"),
            array("Manningtree","Essex","England"),
            array("North Shoebury","Essex","England"),
            array("Ongar","Essex","England"),
            array("Parkeston","Essex","England"),
            array("Pitsea","Essex","England"),
            array("Prettlewell","Essex","England"),
            array("Rayleigh","Essex","England"),
            array("Rochford","Essex","England"),
            array("Romford","Essex","England"),
            array("Saffron Walden","Essex","England"),
            array("Shoeburyness","Essex","England"),
            array("South Benfleet","Essex","England"),
            array("South Woodham Ferrers","Essex","England"),
            array("Southchurch","Essex","England"),
            array("Southend on Sea","Essex","England"),
            array("Southminster","Essex","England"),
            array("Stanfield le Hope","Essex","England"),
            array("Thaxted","Essex","England"),
            array("Thorpe Bay","Essex","England"),
            array("Tilbury","Essex","England"),
            array("Waltham Abbey","Essex","England"),
            array("Walton on the Naze","Essex","England"),
            array("West Mersea","Essex","England"),
            array("West Thurrock","Essex","England"),
            array("West Tilbury","Essex","England"),
            array("Westcliff on Sea","Essex","England"),
            array("Wickford","Essex","England"),
            array("Witham","Essex","England"),
            array("Wivenhoe","Essex","England"),
            array("Berkeley","Gloucestershire","England"),
            array("Bradley Stoke","Gloucestershire","England"),
            array("Cheltenham","Gloucestershire","England"),
            array("Chipping Campden","Gloucestershire","England"),
            array("Chipping Sodbury","Gloucestershire","England"),
            array("Cinderford","Gloucestershire","England"),
            array("Cirencester","Gloucestershire","England"),
            array("Coleford","Gloucestershire","England"),
            array("Dursley","Gloucestershire","England"),
            array("Fairford","Gloucestershire","England"),
            array("Filton","Gloucestershire","England"),
            array("Gloucester","Gloucestershire","England"),
            array("Kingswood","Gloucestershire","England"),
            array("Lechlade","Gloucestershire","England"),
            array("Lydney","Gloucestershire","England"),
            array("Minchinhampton","Gloucestershire","England"),
            array("Mitcheldean","Gloucestershire","England"),
            array("Moreton in Marsh","Gloucestershire","England"),
            array("Nailsworth","Gloucestershire","England"),
            array("Newent","Gloucestershire","England"),
            array("Northleach","Gloucestershire","England"),
            array("Painswick","Gloucestershire","England"),
            array("Patchway","Gloucestershire","England"),
            array("Stonehouse","Gloucestershire","England"),
            array("Stow on the Wold","Gloucestershire","England"),
            array("Stroud","Gloucestershire","England"),
            array("Tetbury","Gloucestershire","England"),
            array("Tewkesbury","Gloucestershire","England"),
            array("Thornbury","Gloucestershire","England"),
            array("Wickwar","Gloucestershire","England"),
            array("Winchcombe","Gloucestershire","England"),
            array("Wotton under Edge","Gloucestershire","England"),
            array("Yate","Gloucestershire","England"),
            array("Acton","Greater London","England"),
            array("Barking","Greater London","England"),
            array("Barnes","Greater London","England"),
            array("Beckenham","Greater London","England"),
            array("Bexley","Greater London","England"),
            array("Brentford","Greater London","England"),
            array("Bromley","Greater London","England"),
            array("Chingford","Greater London","England"),
            array("Croydon","Greater London","England"),
            array("Dagenham","Greater London","England"),
            array("Ealing","Greater London","England"),
            array("East Ham","Greater London","England"),
            array("Edgware","Greater London","England"),
            array("Edmonton","Greater London","England"),
            array("Enfield","Greater London","England"),
            array("Erith","Greater London","England"),
            array("Finchley","Greater London","England"),
            array("Harrow","Greater London","England"),
            array("Hendon","Greater London","England"),
            array("Hornsey","Greater London","England"),
            array("Ilford","Greater London","England"),
            array("Kingston upon Thames","Greater London","England"),
            array("Leyton","Greater London","England"),
            array("Mitcham","Greater London","England"),
            array("Richmond","Greater London","England"),
            array("Southall","Greater London","England"),
            array("Southgate","Greater London","England"),
            array("St Mary Cray","Greater London","England"),
            array("Surbiton","Greater London","England"),
            array("Tottenham","Greater London","England"),
            array("Twickenham","Greater London","England"),
            array("Uxbridge","Greater London","England"),
            array("Walthamstow","Greater London","England"),
            array("Wembley","Greater London","England"),
            array("West Ham","Greater London","England"),
            array("Willesden","Greater London","England"),
            array("Wimbledon","Greater London","England"),
            array("Wood Green","Greater London","England"),
            array("Altrincham","Greater Manchester","England"),
            array("Ashton in Makerfield","Greater Manchester","England"),
            array("Ashton under Lyne","Greater Manchester","England"),
            array("Atherton","Greater Manchester","England"),
            array("Audenshaw","Greater Manchester","England"),
            array("Blackrod","Greater Manchester","England"),
            array("Bolton","Greater Manchester","England"),
            array("Bury","Greater Manchester","England"),
            array("Cadishead","Greater Manchester","England"),
            array("Chadderton","Greater Manchester","England"),
            array("Cheadle","Greater Manchester","England"),
            array("Cheadle Hulme","Greater Manchester","England"),
            array("Denton","Greater Manchester","England"),
            array("Droylsden","Greater Manchester","England"),
            array("Dukinfield","Greater Manchester","England"),
            array("Eccles","Greater Manchester","England"),
            array("Failsworth","Greater Manchester","England"),
            array("Farnworth","Greater Manchester","England"),
            array("Golbourne","Greater Manchester","England"),
            array("Heywood","Greater Manchester","England"),
            array("Hindley","Greater Manchester","England"),
            array("Horwich","Greater Manchester","England"),
            array("Hyde","Greater Manchester","England"),
            array("Ince in Makerfield","Greater Manchester","England"),
            array("Irlam","Greater Manchester","England"),
            array("Kearsley","Greater Manchester","England"),
            array("Leigh","Greater Manchester","England"),
            array("Littleborough","Greater Manchester","England"),
            array("Manchester","Greater Manchester","England"),
            array("Middleton","Greater Manchester","England"),
            array("Milnrow","Greater Manchester","England"),
            array("Mossley","Greater Manchester","England"),
            array("Oldham","Greater Manchester","England"),
            array("Partington","Greater Manchester","England"),
            array("Pendlebury","Greater Manchester","England"),
            array("Prestwich","Greater Manchester","England"),
            array("Radcliffe","Greater Manchester","England"),
            array("Ramsbottom","Greater Manchester","England"),
            array("Rochdale","Greater Manchester","England"),
            array("Royton","Greater Manchester","England"),
            array("Sale","Greater Manchester","England"),
            array("Salford","Greater Manchester","England"),
            array("Shaw and Crompton","Greater Manchester","England"),
            array("Stalybridge","Greater Manchester","England"),
            array("Stockport","Greater Manchester","England"),
            array("Stretford","Greater Manchester","England"),
            array("Swinton","Greater Manchester","England"),
            array("Tottington","Greater Manchester","England"),
            array("Tyldsley","Greater Manchester","England"),
            array("Walkden","Greater Manchester","England"),
            array("Westhoughton","Greater Manchester","England"),
            array("Whitefield","Greater Manchester","England"),
            array("Wigan","Greater Manchester","England"),
            array("Worsley","Greater Manchester","England"),
            array("Aldershot","Hampshire","England"),
            array("Alton","Hampshire","England"),
            array("Andover","Hampshire","England"),
            array("Basingstoke","Hampshire","England"),
            array("Bishop's Waltham","Hampshire","England"),
            array("Blackwater and Hawley","Hampshire","England"),
            array("Bordon","Hampshire","England"),
            array("Eastleigh","Hampshire","England"),
            array("Emsworth","Hampshire","England"),
            array("Fareham","Hampshire","England"),
            array("Farnborough","Hampshire","England"),
            array("Fleet","Hampshire","England"),
            array("Fordingbridge","Hampshire","England"),
            array("Gosport","Hampshire","England"),
            array("Havant","Hampshire","England"),
            array("Hedge End","Hampshire","England"),
            array("Hythe","Hampshire","England"),
            array("Lee on the Solent","Hampshire","England"),
            array("Lymington","Hampshire","England"),
            array("Lyndhurst","Hampshire","England"),
            array("New Alresford","Hampshire","England"),
            array("New Milton","Hampshire","England"),
            array("North Camp","Hampshire","England"),
            array("Petersfield","Hampshire","England"),
            array("Portchester","Hampshire","England"),
            array("Portsmouth","Hampshire","England"),
            array("Ringwood","Hampshire","England"),
            array("Romsey","Hampshire","England"),
            array("Southampton","Hampshire","England"),
            array("Southsea","Hampshire","England"),
            array("Southwick","Hampshire","England"),
            array("Tadley","Hampshire","England"),
            array("Totton","Hampshire","England"),
            array("Waterlooville","Hampshire","England"),
            array("Whitchurch","Hampshire","England"),
            array("Whitehill","Hampshire","England"),
            array("Wickham","Hampshire","England"),
            array("Winchester","Hampshire","England"),
            array("Yateley","Hampshire","England"),
            array("Bromyard","Herefordshire","England"),
            array("Hatfield","Herefordshire","England"),
            array("Hereford","Herefordshire","England"),
            array("Kington","Herefordshire","England"),
            array("Ledbury","Herefordshire","England"),
            array("Leominster","Herefordshire","England"),
            array("Longtown","Herefordshire","England"),
            array("Ross on Wye","Herefordshire","England"),
            array("Baldock","Hertfordshire","England"),
            array("Barnet","Hertfordshire","England"),
            array("Berkhamsted","Hertfordshire","England"),
            array("Bishop's Stortford","Hertfordshire","England"),
            array("Borehamwood","Hertfordshire","England"),
            array("Broxbourne","Hertfordshire","England"),
            array("Buntingford","Hertfordshire","England"),
            array("Bushey","Hertfordshire","England"),
            array("Cheshunt","Hertfordshire","England"),
            array("Chorleywood","Hertfordshire","England"),
            array("Elstree","Hertfordshire","England"),
            array("Harpenden","Hertfordshire","England"),
            array("Hatfield","Hertfordshire","England"),
            array("Hemel Hempstead","Hertfordshire","England"),
            array("Hertford","Hertfordshire","England"),
            array("Hitchin","Hertfordshire","England"),
            array("Hoddesdon","Hertfordshire","England"),
            array("Letchworth","Hertfordshire","England"),
            array("Potters Bar","Hertfordshire","England"),
            array("Rickmansworth","Hertfordshire","England"),
            array("Royston","Hertfordshire","England"),
            array("Sawbridgeworth","Hertfordshire","England"),
            array("Stevenage","Hertfordshire","England"),
            array("Tring","Hertfordshire","England"),
            array("Waltham Cross","Hertfordshire","England"),
            array("Ware","Hertfordshire","England"),
            array("Watford","Hertfordshire","England"),
            array("Welwyn Garden City","Hertfordshire","England"),
            array("Appley","Isle of Wight","England"),
            array("Brading","Isle of Wight","England"),
            array("Cowes","Isle of Wight","England"),
            array("East Cowes","Isle of Wight","England"),
            array("Newport","Isle of Wight","England"),
            array("Ryde","Isle of Wight","England"),
            array("Sandown","Isle of Wight","England"),
            array("Shanklin","Isle of Wight","England"),
            array("Ventnor","Isle of Wight","England"),
            array("Yarmouth","Isle of Wight","England"),
            array("Ashford","Kent","England"),
            array("Broadstairs","Kent","England"),
            array("Canterbury","Kent","England"),
            array("Chatham","Kent","England"),
            array("Cranbrook","Kent","England"),
            array("Crayford","Kent","England"),
            array("Dartford","Kent","England"),
            array("Deal","Kent","England"),
            array("Dover","Kent","England"),
            array("Edenbridge","Kent","England"),
            array("Faversham","Kent","England"),
            array("Folkestone","Kent","England"),
            array("Fordwich","Kent","England"),
            array("Gillingham","Kent","England"),
            array("Gravesend","Kent","England"),
            array("Greenhill","Kent","England"),
            array("Herne Bay","Kent","England"),
            array("Hythe","Kent","England"),
            array("Lydd","Kent","England"),
            array("Maidstone","Kent","England"),
            array("Margate","Kent","England"),
            array("Minster","Kent","England"),
            array("New Romney","Kent","England"),
            array("Northfleet","Kent","England"),
            array("Orpington","Kent","England"),
            array("Paddock Wood","Kent","England"),
            array("Queenborough","Kent","England"),
            array("Rainham","Kent","England"),
            array("Ramsgate","Kent","England"),
            array("Rochester","Kent","England"),
            array("Royal Tunbridge Wells","Kent","England"),
            array("Sandwich","Kent","England"),
            array("Sevenoaks","Kent","England"),
            array("Sheerness","Kent","England"),
            array("Sittingbourne","Kent","England"),
            array("Snodland","Kent","England"),
            array("Southborough","Kent","England"),
            array("Strood","Kent","England"),
            array("Swanley","Kent","England"),
            array("Swanscombe and Greenhithe","Kent","England"),
            array("Tenterden","Kent","England"),
            array("Tonbridge","Kent","England"),
            array("Tunbridge Wells","Kent","England"),
            array("West Malling","Kent","England"),
            array("Westerham","Kent","England"),
            array("Westgate on Sea","Kent","England"),
            array("Whitstable","Kent","England"),
            array("Accrington","Lancashire","England"),
            array("Adlington","Lancashire","England"),
            array("Bacup","Lancashire","England"),
            array("Barnoldswick","Lancashire","England"),
            array("Blackburn","Lancashire","England"),
            array("Blackpool","Lancashire","England"),
            array("Brierfield","Lancashire","England"),
            array("Burnley","Lancashire","England"),
            array("Carnforth","Lancashire","England"),
            array("Chorley","Lancashire","England"),
            array("Clayton le Moors","Lancashire","England"),
            array("Cleveleys","Lancashire","England"),
            array("Clitheroe","Lancashire","England"),
            array("Colne","Lancashire","England"),
            array("Darwen","Lancashire","England"),
            array("Failsworth","Lancashire","England"),
            array("Fleetwood","Lancashire","England"),
            array("Garstang","Lancashire","England"),
            array("Great Harwood","Lancashire","England"),
            array("Haslingden","Lancashire","England"),
            array("Kirkham","Lancashire","England"),
            array("Lancaster","Lancashire","England"),
            array("Leyland","Lancashire","England"),
            array("Longridge","Lancashire","England"),
            array("Lytham St Annes","Lancashire","England"),
            array("Medlar with Wesham","Lancashire","England"),
            array("Morecambe","Lancashire","England"),
            array("Nelson","Lancashire","England"),
            array("Ormskirk","Lancashire","England"),
            array("Oswaldtwistle","Lancashire","England"),
            array("Padiham","Lancashire","England"),
            array("Penwortham","Lancashire","England"),
            array("Poulton le Fylde","Lancashire","England"),
            array("Preesall","Lancashire","England"),
            array("Preston","Lancashire","England"),
            array("Rawtenstall","Lancashire","England"),
            array("Skelmersdale","Lancashire","England"),
            array("Thornton","Lancashire","England"),
            array("Thornton Cleveleys","Lancashire","England"),
            array("Wesham","Lancashire","England"),
            array("Whitworth","Lancashire","England"),
            array("Ashby de la Zouch","Leicestershire","England"),
            array("Ashby Woulds","Leicestershire","England"),
            array("Braunstone Town","Leicestershire","England"),
            array("Coalville","Leicestershire","England"),
            array("Earl Shilton","Leicestershire","England"),
            array("Hinckley","Leicestershire","England"),
            array("Leicester","Leicestershire","England"),
            array("Loughborough","Leicestershire","England"),
            array("Lutterworth","Leicestershire","England"),
            array("Market Bosworth","Leicestershire","England"),
            array("Market Harborough","Leicestershire","England"),
            array("Melton Mowbray","Leicestershire","England"),
            array("Oadby","Leicestershire","England"),
            array("Shepshed","Leicestershire","England"),
            array("Syston","Leicestershire","England"),
            array("Wigston Magna","Leicestershire","England"),
            array("Alford","Lincolnshire","England"),
            array("Barton upon Humber","Lincolnshire","England"),
            array("Boston","Lincolnshire","England"),
            array("Bottesford","Lincolnshire","England"),
            array("Bourne","Lincolnshire","England"),
            array("Brigg","Lincolnshire","England"),
            array("Broughton","Lincolnshire","England"),
            array("Burgh le Marsh","Lincolnshire","England"),
            array("Caistor","Lincolnshire","England"),
            array("Cleethorpes","Lincolnshire","England"),
            array("Coningsby","Lincolnshire","England"),
            array("Crowland","Lincolnshire","England"),
            array("Crowle","Lincolnshire","England"),
            array("Epworth","Lincolnshire","England"),
            array("Gainsborough","Lincolnshire","England"),
            array("Grantham","Lincolnshire","England"),
            array("Grimsby","Lincolnshire","England"),
            array("Holbeach","Lincolnshire","England"),
            array("Horncastle","Lincolnshire","England"),
            array("Immingham","Lincolnshire","England"),
            array("Kirton in Lindsey","Lincolnshire","England"),
            array("Lincoln","Lincolnshire","England"),
            array("Little Coates","Lincolnshire","England"),
            array("Long Sutton","Lincolnshire","England"),
            array("Louth","Lincolnshire","England"),
            array("Mablethorpe","Lincolnshire","England"),
            array("Mablethorpe and Sutton","Lincolnshire","England"),
            array("Market Deeping","Lincolnshire","England"),
            array("Market Rasen","Lincolnshire","England"),
            array("North Hykeham","Lincolnshire","England"),
            array("Scunthorpe","Lincolnshire","England"),
            array("Skegness","Lincolnshire","England"),
            array("Sleaford","Lincolnshire","England"),
            array("Spalding","Lincolnshire","England"),
            array("Spilsby","Lincolnshire","England"),
            array("Stamford","Lincolnshire","England"),
            array("The Deepings","Lincolnshire","England"),
            array("Wainfleet","Lincolnshire","England"),
            array("Winterton","Lincolnshire","England"),
            array("Wragby","Lincolnshire","England"),
            array("Bebington","Merseyside","England"),
            array("Birkenhead","Merseyside","England"),
            array("Bootle","Merseyside","England"),
            array("Bromborough","Merseyside","England"),
            array("Crosby","Merseyside","England"),
            array("Earlestown","Merseyside","England"),
            array("Formby","Merseyside","England"),
            array("Halewood","Merseyside","England"),
            array("Heswall","Merseyside","England"),
            array("Hoylake","Merseyside","England"),
            array("Huyton","Merseyside","England"),
            array("Kirkby","Merseyside","England"),
            array("Liverpool","Merseyside","England"),
            array("Maghull","Merseyside","England"),
            array("Newton le Willows","Merseyside","England"),
            array("Prescot","Merseyside","England"),
            array("Rainford","Merseyside","England"),
            array("Rainhill","Merseyside","England"),
            array("Southport","Merseyside","England"),
            array("St Helens","Merseyside","England"),
            array("Wallasey","Merseyside","England"),
            array("Whiston","Merseyside","England"),
            array("Acle","Norfolk","England"),
            array("Attleborough","Norfolk","England"),
            array("Aylsham","Norfolk","England"),
            array("Caister on Sea","Norfolk","England"),
            array("Cromer","Norfolk","England"),
            array("Dereham","Norfolk","England"),
            array("Diss","Norfolk","England"),
            array("Downham Market","Norfolk","England"),
            array("Fakenham","Norfolk","England"),
            array("Gorleston","Norfolk","England"),
            array("Great Yarmouth","Norfolk","England"),
            array("Hingham","Norfolk","England"),
            array("Holt","Norfolk","England"),
            array("Hunstanton","Norfolk","England"),
            array("King's Lynn","Norfolk","England"),
            array("Loddon","Norfolk","England"),
            array("North Walsham","Norfolk","England"),
            array("Norwich","Norfolk","England"),
            array("Rackheath","Norfolk","England"),
            array("Redenhall with Harleston","Norfolk","England"),
            array("Reepham","Norfolk","England"),
            array("Sheringham","Norfolk","England"),
            array("Stalham","Norfolk","England"),
            array("Swaffham","Norfolk","England"),
            array("Thetford","Norfolk","England"),
            array("Thorpe St Andrew","Norfolk","England"),
            array("Watton","Norfolk","England"),
            array("Wells next the Sea","Norfolk","England"),
            array("Wroxham","Norfolk","England"),
            array("Wymondham","Norfolk","England"),
            array("Bedale","North Yorkshire","England"),
            array("Bentham","North Yorkshire","England"),
            array("Boroughbridge","North Yorkshire","England"),
            array("Colburn","North Yorkshire","England"),
            array("Easingwold","North Yorkshire","England"),
            array("Eston","North Yorkshire","England"),
            array("Filey","North Yorkshire","England"),
            array("Grangetown","North Yorkshire","England"),
            array("Grassington","North Yorkshire","England"),
            array("Guisborough","North Yorkshire","England"),
            array("Harrogate","North Yorkshire","England"),
            array("Hawes","North Yorkshire","England"),
            array("Haxby","North Yorkshire","England"),
            array("Helmsley","North Yorkshire","England"),
            array("Ingleby Barwick","North Yorkshire","England"),
            array("Kirkbymoorside","North Yorkshire","England"),
            array("Knaresborough","North Yorkshire","England"),
            array("Leyburn","North Yorkshire","England"),
            array("Loftus","North Yorkshire","England"),
            array("Malton","North Yorkshire","England"),
            array("Masham","North Yorkshire","England"),
            array("Middleham","North Yorkshire","England"),
            array("Middlesbrough","North Yorkshire","England"),
            array("Northallerton","North Yorkshire","England"),
            array("Norton on Derwent","North Yorkshire","England"),
            array("Pateley Bridge","North Yorkshire","England"),
            array("Pickering","North Yorkshire","England"),
            array("Redcar","North Yorkshire","England"),
            array("Richmond","North Yorkshire","England"),
            array("Ripon","North Yorkshire","England"),
            array("Robin Hood's Bay","North Yorkshire","England"),
            array("Saltburn by the Sea","North Yorkshire","England"),
            array("Scarborough","North Yorkshire","England"),
            array("Selby","North Yorkshire","England"),
            array("Settle","North Yorkshire","England"),
            array("Sherburn in Elmet","North Yorkshire","England"),
            array("Skelton in Cleveland","North Yorkshire","England"),
            array("Skipton","North Yorkshire","England"),
            array("Stockton on Tees","North Yorkshire","England"),
            array("Stokesley","North Yorkshire","England"),
            array("Tadcaster","North Yorkshire","England"),
            array("Thirsk","North Yorkshire","England"),
            array("Thornaby on Tees","North Yorkshire","England"),
            array("Whitby","North Yorkshire","England"),
            array("Yarm","North Yorkshire","England"),
            array("York","North Yorkshire","England"),
            array("Brackley","Northamptonshire","England"),
            array("Burton Latimer","Northamptonshire","England"),
            array("Corby","Northamptonshire","England"),
            array("Daventry","Northamptonshire","England"),
            array("Desborough","Northamptonshire","England"),
            array("Higham Ferrers","Northamptonshire","England"),
            array("Irthlingborough","Northamptonshire","England"),
            array("Kettering","Northamptonshire","England"),
            array("Northampton","Northamptonshire","England"),
            array("Oundle","Northamptonshire","England"),
            array("Raunds","Northamptonshire","England"),
            array("Rothwell","Northamptonshire","England"),
            array("Rushden","Northamptonshire","England"),
            array("Thrapston","Northamptonshire","England"),
            array("Towcester","Northamptonshire","England"),
            array("Wellingborough","Northamptonshire","England"),
            array("Alnwick","Northumberland","England"),
            array("Amble","Northumberland","England"),
            array("Ashington","Northumberland","England"),
            array("Bedlington","Northumberland","England"),
            array("Berwick upon Tweed","Northumberland","England"),
            array("Blyth","Northumberland","England"),
            array("Corbridge","Northumberland","England"),
            array("Cramlington","Northumberland","England"),
            array("Haltwhistle","Northumberland","England"),
            array("Hexham","Northumberland","England"),
            array("Morpeth","Northumberland","England"),
            array("Newbiggin by the Sea","Northumberland","England"),
            array("Ponteland","Northumberland","England"),
            array("Prudhoe","Northumberland","England"),
            array("Rothbury","Northumberland","England"),
            array("West Bedlington","Northumberland","England"),
            array("Wooler","Northumberland","England"),
            array("Arnold","Nottinghamshire","England"),
            array("Beeston","Nottinghamshire","England"),
            array("Bingham","Nottinghamshire","England"),
            array("Bracebridge","Nottinghamshire","England"),
            array("Bulwell","Nottinghamshire","England"),
            array("Carlton","Nottinghamshire","England"),
            array("Cotgrave","Nottinghamshire","England"),
            array("East Retford","Nottinghamshire","England"),
            array("Eastwood","Nottinghamshire","England"),
            array("Harworth and Bircotes","Nottinghamshire","England"),
            array("Hucknall","Nottinghamshire","England"),
            array("Kilton","Nottinghamshire","England"),
            array("Kimberley","Nottinghamshire","England"),
            array("Kirkby in Ashfield","Nottinghamshire","England"),
            array("Mansfield","Nottinghamshire","England"),
            array("Netherfield","Nottinghamshire","England"),
            array("Newark on Trent","Nottinghamshire","England"),
            array("Nottingham","Nottinghamshire","England"),
            array("Ollerton","Nottinghamshire","England"),
            array("Ollerton and Boughton","Nottinghamshire","England"),
            array("Retford","Nottinghamshire","England"),
            array("Southwell","Nottinghamshire","England"),
            array("Stapleford","Nottinghamshire","England"),
            array("Sutton in Ashfield","Nottinghamshire","England"),
            array("Warsop","Nottinghamshire","England"),
            array("West Bridgefield","Nottinghamshire","England"),
            array("Worksop","Nottinghamshire","England"),
            array("Abingdon","Oxfordshire","England"),
            array("Banbury","Oxfordshire","England"),
            array("Bicester","Oxfordshire","England"),
            array("Burford","Oxfordshire","England"),
            array("Carterton","Oxfordshire","England"),
            array("Charlbury","Oxfordshire","England"),
            array("Chipping Norton","Oxfordshire","England"),
            array("Didcot","Oxfordshire","England"),
            array("Dorchester","Oxfordshire","England"),
            array("Faringdon","Oxfordshire","England"),
            array("Henley on Thames","Oxfordshire","England"),
            array("Neithrop","Oxfordshire","England"),
            array("Oxford","Oxfordshire","England"),
            array("Ruscote","Oxfordshire","England"),
            array("Thame","Oxfordshire","England"),
            array("Wallingford","Oxfordshire","England"),
            array("Wantage","Oxfordshire","England"),
            array("Watlington","Oxfordshire","England"),
            array("Weston Otmoor","Oxfordshire","England"),
            array("Witney","Oxfordshire","England"),
            array("Woodstock","Oxfordshire","England"),
            array("Oakham","Rutland","England"),
            array("Uppingham","Rutland","England"),
            array("Bishop's Castle","Shropshire","England"),
            array("Bridgnorth","Shropshire","England"),
            array("Broseley","Shropshire","England"),
            array("Church Stretton","Shropshire","England"),
            array("Cleobury Mortimer","Shropshire","England"),
            array("Clun","Shropshire","England"),
            array("Craven Arms","Shropshire","England"),
            array("Dawley","Shropshire","England"),
            array("Ellesmere","Shropshire","England"),
            array("Ludlow","Shropshire","England"),
            array("Madeley","Shropshire","England"),
            array("Market Drayton","Shropshire","England"),
            array("Much Wenlock","Shropshire","England"),
            array("Newport","Shropshire","England"),
            array("Oakengates","Shropshire","England"),
            array("Oswestry","Shropshire","England"),
            array("Shifnal","Shropshire","England"),
            array("Shrewsbury","Shropshire","England"),
            array("Telford","Shropshire","England"),
            array("Wellington","Shropshire","England"),
            array("Wem","Shropshire","England"),
            array("Whitchurch","Shropshire","England"),
            array("Axbridge","Somerset","England"),
            array("Bath","Somerset","England"),
            array("Bridgwater","Somerset","England"),
            array("Bruton","Somerset","England"),
            array("Burnham on Sea","Somerset","England"),
            array("Castle Cary","Somerset","England"),
            array("Chard","Somerset","England"),
            array("Clevedon","Somerset","England"),
            array("Crewkerne","Somerset","England"),
            array("Dulverton","Somerset","England"),
            array("Frome","Somerset","England"),
            array("Glastonbury","Somerset","England"),
            array("Highbridge","Somerset","England"),
            array("Ilminster","Somerset","England"),
            array("Keynsham","Somerset","England"),
            array("Langport","Somerset","England"),
            array("Midsomer Norton","Somerset","England"),
            array("Minehead","Somerset","England"),
            array("Nailsea","Somerset","England"),
            array("North Petherton","Somerset","England"),
            array("Norton Radstock","Somerset","England"),
            array("Portishead","Somerset","England"),
            array("Portishead and North Weston","Somerset","England"),
            array("Radstock","Somerset","England"),
            array("Shepton Mallet","Somerset","England"),
            array("Somerton","Somerset","England"),
            array("South Petherton","Somerset","England"),
            array("Street","Somerset","England"),
            array("Taunton","Somerset","England"),
            array("Watchet","Somerset","England"),
            array("Wellington","Somerset","England"),
            array("Wells","Somerset","England"),
            array("Weston super Mare","Somerset","England"),
            array("Wincanton","Somerset","England"),
            array("Winsford","Somerset","England"),
            array("Wiveliscombe","Somerset","England"),
            array("Yeovil","Somerset","England"),
            array("Anston","South Yorkshire","England"),
            array("Askern","South Yorkshire","England"),
            array("Barnsley","South Yorkshire","England"),
            array("Bawtry","South Yorkshire","England"),
            array("Brierley","South Yorkshire","England"),
            array("Conisbrough","South Yorkshire","England"),
            array("Dinnington","South Yorkshire","England"),
            array("Doncaster","South Yorkshire","England"),
            array("Edlington","South Yorkshire","England"),
            array("Hatfield","South Yorkshire","England"),
            array("Hoyland","South Yorkshire","England"),
            array("Maltby","South Yorkshire","England"),
            array("Mexborough","South Yorkshire","England"),
            array("Penistone","South Yorkshire","England"),
            array("Rotherham","South Yorkshire","England"),
            array("Sheffield","South Yorkshire","England"),
            array("Stainforth","South Yorkshire","England"),
            array("Stocksbridge","South Yorkshire","England"),
            array("Swinton","South Yorkshire","England"),
            array("Thorne","South Yorkshire","England"),
            array("Tickhill","South Yorkshire","England"),
            array("Wath upon Dearne","South Yorkshire","England"),
            array("Wombwell","South Yorkshire","England"),
            array("Alton","Staffordshire","England"),
            array("Biddulph","Staffordshire","England"),
            array("Burntwood","Staffordshire","England"),
            array("Burslem","Staffordshire","England"),
            array("Burton upon Trent","Staffordshire","England"),
            array("Cannock","Staffordshire","England"),
            array("Cheadle","Staffordshire","England"),
            array("Eccleshall","Staffordshire","England"),
            array("Fazeley","Staffordshire","England"),
            array("Fenton","Staffordshire","England"),
            array("Hednesford","Staffordshire","England"),
            array("Kidsgrove","Staffordshire","England"),
            array("Leek","Staffordshire","England"),
            array("Lichfield","Staffordshire","England"),
            array("Longton","Staffordshire","England"),
            array("Newcastle under Lyme","Staffordshire","England"),
            array("Penkridge","Staffordshire","England"),
            array("Perry Crofts","Staffordshire","England"),
            array("Rugeley","Staffordshire","England"),
            array("Stafford","Staffordshire","England"),
            array("Stoke on Trent","Staffordshire","England"),
            array("Stone","Staffordshire","England"),
            array("Tamworth","Staffordshire","England"),
            array("Tunstall","Staffordshire","England"),
            array("Uttoxeter","Staffordshire","England"),
            array("Aldeburgh","Suffolk","England"),
            array("Beccles","Suffolk","England"),
            array("Brandon","Suffolk","England"),
            array("Bungay","Suffolk","England"),
            array("Bury St Edmunds","Suffolk","England"),
            array("Carlton Colville","Suffolk","England"),
            array("Clare","Suffolk","England"),
            array("Dommoc","Suffolk","England"),
            array("Dunwich","Suffolk","England"),
            array("Eye","Suffolk","England"),
            array("Felixstowe","Suffolk","England"),
            array("Framlingham","Suffolk","England"),
            array("Hadleigh","Suffolk","England"),
            array("Halesworth","Suffolk","England"),
            array("Haverhill","Suffolk","England"),
            array("Ipswich","Suffolk","England"),
            array("Kesgrave","Suffolk","England"),
            array("Leiston","Suffolk","England"),
            array("Lowestoft","Suffolk","England"),
            array("Mildenhall","Suffolk","England"),
            array("Needham Market","Suffolk","England"),
            array("Newmarket","Suffolk","England"),
            array("Orford","Suffolk","England"),
            array("Otley","Suffolk","England"),
            array("Saxmundham","Suffolk","England"),
            array("Southwold","Suffolk","England"),
            array("Stowmarket","Suffolk","England"),
            array("Sudbury","Suffolk","England"),
            array("Woodbridge","Suffolk","England"),
            array("Ashford","Surrey","England"),
            array("Beltchingley","Surrey","England"),
            array("Camberley","Surrey","England"),
            array("Chertsey","Surrey","England"),
            array("Croydon","Surrey","England"),
            array("Dorking","Surrey","England"),
            array("Egham","Surrey","England"),
            array("Epsom","Surrey","England"),
            array("Esher","Surrey","England"),
            array("Farnham","Surrey","England"),
            array("Godalming","Surrey","England"),
            array("Gomshall","Surrey","England"),
            array("Gratton","Surrey","England"),
            array("Great Brookham","Surrey","England"),
            array("Guildford","Surrey","England"),
            array("Haslemere","Surrey","England"),
            array("Horley","Surrey","England"),
            array("Kingston upon Thames","Surrey","England"),
            array("Leatherhead","Surrey","England"),
            array("Redhill","Surrey","England"),
            array("Reigate","Surrey","England"),
            array("Southwark","Surrey","England"),
            array("Staines upon Thames","Surrey","England"),
            array("Walton on Thames","Surrey","England"),
            array("Weybridge","Surrey","England"),
            array("Woking","Surrey","England"),
            array("Birtley","Tyne and Wear","England"),
            array("Blaydon on tyne","Tyne and Wear","England"),
            array("Cullercoats","Tyne and Wear","England"),
            array("Darsley Park","Tyne and Wear","England"),
            array("Dunston","Tyne and Wear","England"),
            array("Gateshead","Tyne and Wear","England"),
            array("Hetton","Tyne and Wear","England"),
            array("Houghton le Spring","Tyne and Wear","England"),
            array("Howdon","Tyne and Wear","England"),
            array("Jarrow","Tyne and Wear","England"),
            array("Killingworth","Tyne and Wear","England"),
            array("Little Benton","Tyne and Wear","England"),
            array("Longbenton","Tyne and Wear","England"),
            array("Low Fell","Tyne and Wear","England"),
            array("Newcastle upon Tyne","Tyne and Wear","England"),
            array("North Shields","Tyne and Wear","England"),
            array("Ryton","Tyne and Wear","England"),
            array("Sheriff Hill","Tyne and Wear","England"),
            array("South Shields","Tyne and Wear","England"),
            array("Sunderland","Tyne and Wear","England"),
            array("Tynemouth","Tyne and Wear","England"),
            array("Wallsend","Tyne and Wear","England"),
            array("Washington","Tyne and Wear","England"),
            array("Whitley Bay","Tyne and Wear","England"),
            array("Willington Quay","Tyne and Wear","England"),
            array("Windy Nook","Tyne and Wear","England"),
            array("Alcester","Warwickshire","England"),
            array("Atherstone","Warwickshire","England"),
            array("Bedworth","Warwickshire","England"),
            array("Coleshill","Warwickshire","England"),
            array("Henley in Arden","Warwickshire","England"),
            array("Kenilworth","Warwickshire","England"),
            array("Middle Quinton","Warwickshire","England"),
            array("Nuneaton","Warwickshire","England"),
            array("Royal Leamington Spa","Warwickshire","England"),
            array("Rugby","Warwickshire","England"),
            array("Shipston on Stour","Warwickshire","England"),
            array("Southam","Warwickshire","England"),
            array("Stratford upon Avon","Warwickshire","England"),
            array("Warwick","Warwickshire","England"),
            array("Whitnash","Warwickshire","England"),
            array("Aldridge","West Midlands","England"),
            array("Bilston","West Midlands","England"),
            array("Birmingham","West Midlands","England"),
            array("Blackheath","West Midlands","England"),
            array("Bloxwich","West Midlands","England"),
            array("Brierley Hill","West Midlands","England"),
            array("Brownhills","West Midlands","England"),
            array("Coal Pool","West Midlands","England"),
            array("Coseley","West Midlands","England"),
            array("Coventry","West Midlands","England"),
            array("Cradley Heath","West Midlands","England"),
            array("Darlaston","West Midlands","England"),
            array("Dudley","West Midlands","England"),
            array("Fordbridge","West Midlands","England"),
            array("Gornal","West Midlands","England"),
            array("Halesowen","West Midlands","England"),
            array("Lye","West Midlands","England"),
            array("Moxley","West Midlands","England"),
            array("Netherton","West Midlands","England"),
            array("Oldbury","West Midlands","England"),
            array("Pelsall","West Midlands","England"),
            array("Rowley Regis","West Midlands","England"),
            array("Sedgley","West Midlands","England"),
            array("Smethwick","West Midlands","England"),
            array("Solihull","West Midlands","England"),
            array("Stourbridge","West Midlands","England"),
            array("Sutton Coldfield","West Midlands","England"),
            array("Tipton","West Midlands","England"),
            array("Walsall","West Midlands","England"),
            array("Wednesbury","West Midlands","England"),
            array("Wednesfield","West Midlands","England"),
            array("West Bromwich","West Midlands","England"),
            array("Willenhall","West Midlands","England"),
            array("Wolverhampton","West Midlands","England"),
            array("Arundel","West Sussex","England"),
            array("Bognor Regis","West Sussex","England"),
            array("Burgess Hill","West Sussex","England"),
            array("Chichester","West Sussex","England"),
            array("Crawley","West Sussex","England"),
            array("Cuckfield","West Sussex","England"),
            array("East Grinstead","West Sussex","England"),
            array("Haywards Heath","West Sussex","England"),
            array("Horsham","West Sussex","England"),
            array("Littlehampton","West Sussex","England"),
            array("Midhurst","West Sussex","England"),
            array("Petworth","West Sussex","England"),
            array("Selsey","West Sussex","England"),
            array("Shoreham by Sea","West Sussex","England"),
            array("Southwick","West Sussex","England"),
            array("Steyning","West Sussex","England"),
            array("Worthing","West Sussex","England"),
            array("Baildon","West Yorkshire","England"),
            array("Batley","West Yorkshire","England"),
            array("Bingley","West Yorkshire","England"),
            array("Bradford","West Yorkshire","England"),
            array("Brighouse","West Yorkshire","England"),
            array("Castleford","West Yorkshire","England"),
            array("Cleckheaton","West Yorkshire","England"),
            array("Denholme","West Yorkshire","England"),
            array("Dewsbury","West Yorkshire","England"),
            array("Elland","West Yorkshire","England"),
            array("Farsley","West Yorkshire","England"),
            array("Featherstone","West Yorkshire","England"),
            array("Garforth","West Yorkshire","England"),
            array("Guiseley","West Yorkshire","England"),
            array("Halifax","West Yorkshire","England"),
            array("Hebden Bridge","West Yorkshire","England"),
            array("Hebden Royd","West Yorkshire","England"),
            array("Heckmondwike","West Yorkshire","England"),
            array("Hemsworth","West Yorkshire","England"),
            array("Holmfirth","West Yorkshire","England"),
            array("Horsforth","West Yorkshire","England"),
            array("Huddersfield","West Yorkshire","England"),
            array("Ilkley","West Yorkshire","England"),
            array("Keighley","West Yorkshire","England"),
            array("Knottingley","West Yorkshire","England"),
            array("Leeds","West Yorkshire","England"),
            array("Meltham","West Yorkshire","England"),
            array("Mirfield","West Yorkshire","England"),
            array("Morley","West Yorkshire","England"),
            array("Mytholmroyd","West Yorkshire","England"),
            array("Normanton","West Yorkshire","England"),
            array("Ossett","West Yorkshire","England"),
            array("Otley","West Yorkshire","England"),
            array("Pontefract","West Yorkshire","England"),
            array("Pudsey","West Yorkshire","England"),
            array("Rothwell","West Yorkshire","England"),
            array("Shipley","West Yorkshire","England"),
            array("Silsden","West Yorkshire","England"),
            array("South Elmsall","West Yorkshire","England"),
            array("South Kirkby and Moorthorpe","West Yorkshire","England"),
            array("Sowerby Bridge","West Yorkshire","England"),
            array("Todmorden","West Yorkshire","England"),
            array("Wakefield","West Yorkshire","England"),
            array("Wetherby","West Yorkshire","England"),
            array("Yeadon","West Yorkshire","England"),
            array("Amesbury","Wiltshire","England"),
            array("Bradford on Avon","Wiltshire","England"),
            array("Calne","Wiltshire","England"),
            array("Chippenham","Wiltshire","England"),
            array("Corsham","Wiltshire","England"),
            array("Cricklade","Wiltshire","England"),
            array("Devizes","Wiltshire","England"),
            array("Highworth","Wiltshire","England"),
            array("Ludgershall","Wiltshire","England"),
            array("Malmesbury","Wiltshire","England"),
            array("Marlborough","Wiltshire","England"),
            array("Melksham","Wiltshire","England"),
            array("Mere","Wiltshire","England"),
            array("Royal Wootton Bassett","Wiltshire","England"),
            array("Salisbury","Wiltshire","England"),
            array("Swindon","Wiltshire","England"),
            array("Tidworth","Wiltshire","England"),
            array("Tisbury","Wiltshire","England"),
            array("Trowbridge","Wiltshire","England"),
            array("Warminster","Wiltshire","England"),
            array("Westbury","Wiltshire","England"),
            array("Wilton","Wiltshire","England"),
            array("Bewdley","Worcestershire","England"),
            array("Bromsgrove","Worcestershire","England"),
            array("Droitwich Spa","Worcestershire","England"),
            array("Evesham","Worcestershire","England"),
            array("Great Malvern","Worcestershire","England"),
            array("Kidderminster","Worcestershire","England"),
            array("Malvern","Worcestershire","England"),
            array("Pershore","Worcestershire","England"),
            array("Redditch","Worcestershire","England"),
            array("Stourport on Severn","Worcestershire","England"),
            array("Tenbury Wells","Worcestershire","England"),
            array("Upton upon Severn","Worcestershire","England"),
            array("Worcester","Worcestershire","England"),
            array("Antrim","County Antrim","Northern Ireland"),
            array("Ballycastle","County Antrim","Northern Ireland"),
            array("Ballyclare","County Antrim","Northern Ireland"),
            array("Ballymena","County Antrim","Northern Ireland"),
            array("Ballymoney","County Antrim","Northern Ireland"),
            array("Bushmills","County Antrim","Northern Ireland"),
            array("Carrickfergus","County Antrim","Northern Ireland"),
            array("Crumlin","County Antrim","Northern Ireland"),
            array("Greenisland","County Antrim","Northern Ireland"),
            array("Larne","County Antrim","Northern Ireland"),
            array("Lisburn","County Antrim","Northern Ireland"),
            array("Merville Garden Village","County Antrim","Northern Ireland"),
            array("Newtownabbey","County Antrim","Northern Ireland"),
            array("Portrush","County Antrim","Northern Ireland"),
            array("Randalstown","County Antrim","Northern Ireland"),
            array("Armagh","County Armagh","Northern Ireland"),
            array("Craigavon","County Armagh","Northern Ireland"),
            array("Lurgan","County Armagh","Northern Ireland"),
            array("Markethill","County Armagh","Northern Ireland"),
            array("Newry","County Armagh","Northern Ireland"),
            array("Portadown","County Armagh","Northern Ireland"),
            array("Ballynahinch","County Down","Northern Ireland"),
            array("Banbridge","County Down","Northern Ireland"),
            array("Bangor","County Down","Northern Ireland"),
            array("Carryduff","County Down","Northern Ireland"),
            array("Castlewellan","County Down","Northern Ireland"),
            array("Comber","County Down","Northern Ireland"),
            array("Donaghadee","County Down","Northern Ireland"),
            array("Doromore","County Down","Northern Ireland"),
            array("Downpatrick","County Down","Northern Ireland"),
            array("Dundonald","County Down","Northern Ireland"),
            array("Holywood","County Down","Northern Ireland"),
            array("Kilkeel","County Down","Northern Ireland"),
            array("Killyleagh","County Down","Northern Ireland"),
            array("Lisburn","County Down","Northern Ireland"),
            array("Newcastle","County Down","Northern Ireland"),
            array("Newtownards","County Down","Northern Ireland"),
            array("Portaferry","County Down","Northern Ireland"),
            array("Rostrevor","County Down","Northern Ireland"),
            array("Saintfield","County Down","Northern Ireland"),
            array("Warrenpoint","County Down","Northern Ireland"),
            array("Enniskillen","County Fermanagh","Northern Ireland"),
            array("Lisnaskea","County Fermanagh","Northern Ireland"),
            array("Coleraine","County Londonderry","Northern Ireland"),
            array("Derry","County Londonderry","Northern Ireland"),
            array("Limavady","County Londonderry","Northern Ireland"),
            array("Magherafelt","County Londonderry","Northern Ireland"),
            array("Portstewart","County Londonderry","Northern Ireland"),
            array("Castlederg","County Tyrone","Northern Ireland"),
            array("Clogher","County Tyrone","Northern Ireland"),
            array("Coalisland","County Tyrone","Northern Ireland"),
            array("Cookstown","County Tyrone","Northern Ireland"),
            array("Dungannon","County Tyrone","Northern Ireland"),
            array("Fintona","County Tyrone","Northern Ireland"),
            array("Fivemiletown","County Tyrone","Northern Ireland"),
            array("Omagh","County Tyrone","Northern Ireland"),
            array("Strabane","County Tyrone","Northern Ireland"),
            array("Aberdeen","Aberdeenshire","Scotland"),
            array("Alford","Aberdeenshire","Scotland"),
            array("Ballater","Aberdeenshire","Scotland"),
            array("Banchory","Aberdeenshire","Scotland"),
            array("Banff","Aberdeenshire","Scotland"),
            array("Banff and Macduff","Aberdeenshire","Scotland"),
            array("Blackburn","Aberdeenshire","Scotland"),
            array("Boddam","Aberdeenshire","Scotland"),
            array("Clerkhill","Aberdeenshire","Scotland"),
            array("Ellon","Aberdeenshire","Scotland"),
            array("Fraserburgh","Aberdeenshire","Scotland"),
            array("Huntly","Aberdeenshire","Scotland"),
            array("Insch","Aberdeenshire","Scotland"),
            array("Inverbervie","Aberdeenshire","Scotland"),
            array("Inverurie","Aberdeenshire","Scotland"),
            array("Kemnay","Aberdeenshire","Scotland"),
            array("Kintore","Aberdeenshire","Scotland"),
            array("Laurencekirk","Aberdeenshire","Scotland"),
            array("Macduff","Aberdeenshire","Scotland"),
            array("Maud","Aberdeenshire","Scotland"),
            array("Oldmeldrum","Aberdeenshire","Scotland"),
            array("Peterhead","Aberdeenshire","Scotland"),
            array("Portlethen","Aberdeenshire","Scotland"),
            array("Portsoy","Aberdeenshire","Scotland"),
            array("Red Cloak","Aberdeenshire","Scotland"),
            array("Rosehearty","Aberdeenshire","Scotland"),
            array("Stonehaven","Aberdeenshire","Scotland"),
            array("Turriff","Aberdeenshire","Scotland"),
            array("Westhill","Aberdeenshire","Scotland"),
            array("Arbroath","Angus","Scotland"),
            array("Brechin","Angus","Scotland"),
            array("Carnoustie","Angus","Scotland"),
            array("Forfar","Angus","Scotland"),
            array("Kirriemuir","Angus","Scotland"),
            array("Monifieth","Angus","Scotland"),
            array("Montrose","Angus","Scotland"),
            array("Alloa","Clackmannanshire","Scotland"),
            array("Alva","Clackmannanshire","Scotland"),
            array("Clackmannan","Clackmannanshire","Scotland"),
            array("Dollar","Clackmannanshire","Scotland"),
            array("Menstrie","Clackmannanshire","Scotland"),
            array("Tillicoultry","Clackmannanshire","Scotland"),
            array("Tullibody","Clackmannanshire","Scotland"),
            array("Annan","Dumfries and Galloway","Scotland"),
            array("Castle Douglas","Dumfries and Galloway","Scotland"),
            array("Dalbeattie","Dumfries and Galloway","Scotland"),
            array("Dumfries","Dumfries and Galloway","Scotland"),
            array("Gatehouse of Fleet","Dumfries and Galloway","Scotland"),
            array("Gretna","Dumfries and Galloway","Scotland"),
            array("Kelloholm","Dumfries and Galloway","Scotland"),
            array("Kirkconnel","Dumfries and Galloway","Scotland"),
            array("Kirkcudbright","Dumfries and Galloway","Scotland"),
            array("Langholm","Dumfries and Galloway","Scotland"),
            array("Lochmaben","Dumfries and Galloway","Scotland"),
            array("Lockerbie","Dumfries and Galloway","Scotland"),
            array("Moffat","Dumfries and Galloway","Scotland"),
            array("Monreith","Dumfries and Galloway","Scotland"),
            array("Newbridge Drive","Dumfries and Galloway","Scotland"),
            array("Newton Stewart","Dumfries and Galloway","Scotland"),
            array("Sanquhar","Dumfries and Galloway","Scotland"),
            array("Stranraer","Dumfries and Galloway","Scotland"),
            array("Thornhill","Dumfries and Galloway","Scotland"),
            array("Wigtown","Dumfries and Galloway","Scotland"),
            array("Dundee","Dundee","Scotland"),
            array("Cockenzie and Port Seton","East Lothian","Scotland"),
            array("Dunbar","East Lothian","Scotland"),
            array("Haddington","East Lothian","Scotland"),
            array("Musselburgh","East Lothian","Scotland"),
            array("North Berwick","East Lothian","Scotland"),
            array("Prestonpans","East Lothian","Scotland"),
            array("Tranent","East Lothian","Scotland"),
            array("Edinburgh?","Edinburgh?","Scotland"),
            array("Airth","Falkirk","Scotland"),
            array("Bo'ness","Falkirk","Scotland"),
            array("Bonnybridge","Falkirk","Scotland"),
            array("Borrowstounness","Falkirk","Scotland"),
            array("Carron","Falkirk","Scotland"),
            array("Denny","Falkirk","Scotland"),
            array("Duniplace","Falkirk","Scotland"),
            array("Dunmore","Falkirk","Scotland"),
            array("Falkirk","Falkirk","Scotland"),
            array("Grahamston","Falkirk","Scotland"),
            array("Grangemouth","Falkirk","Scotland"),
            array("Larbert","Falkirk","Scotland"),
            array("Stenhousemuir","Falkirk","Scotland"),
            array("Anstruther","Fife","Scotland"),
            array("Auchtermuchty","Fife","Scotland"),
            array("Balcurvie","Fife","Scotland"),
            array("Ballingry","Fife","Scotland"),
            array("Benarty","Fife","Scotland"),
            array("Buckhaven","Fife","Scotland"),
            array("Burntisland","Fife","Scotland"),
            array("Collydean","Fife","Scotland"),
            array("Cowdenbeath","Fife","Scotland"),
            array("Crail","Fife","Scotland"),
            array("Cupar","Fife","Scotland"),
            array("Dalgety Bay","Fife","Scotland"),
            array("Dunfermline","Fife","Scotland"),
            array("Dysart","Fife","Scotland"),
            array("East Wemyss","Fife","Scotland"),
            array("Falkland","Fife","Scotland"),
            array("Glenrothes","Fife","Scotland"),
            array("Inverkeithing","Fife","Scotland"),
            array("Kelty","Fife","Scotland"),
            array("Kincardine","Fife","Scotland"),
            array("Kinghorn","Fife","Scotland"),
            array("Kinglassie","Fife","Scotland"),
            array("Kirkcaldy","Fife","Scotland"),
            array("Ladybank","Fife","Scotland"),
            array("Letham","Fife","Scotland"),
            array("Leuchars","Fife","Scotland"),
            array("Leven","Fife","Scotland"),
            array("Levenmouth","Fife","Scotland"),
            array("Lochgelly","Fife","Scotland"),
            array("Markinch","Fife","Scotland"),
            array("Methil","Fife","Scotland"),
            array("Newburgh","Fife","Scotland"),
            array("Newport on Tay","Fife","Scotland"),
            array("North Queensferry","Fife","Scotland"),
            array("Pitcoudie","Fife","Scotland"),
            array("Pittenweem","Fife","Scotland"),
            array("Rosyth","Fife","Scotland"),
            array("St Andrews","Fife","Scotland"),
            array("St Monans","Fife","Scotland"),
            array("Tayport","Fife","Scotland"),
            array("Woodhaven","Fife","Scotland"),
            array("Wormit","Fife","Scotland"),
            array("Alness","Highlands","Scotland"),
            array("Aviemore","Highlands","Scotland"),
            array("Brora","Highlands","Scotland"),
            array("Cromarty","Highlands","Scotland"),
            array("Dingwall","Highlands","Scotland"),
            array("Dornoch","Highlands","Scotland"),
            array("Fort William","Highlands","Scotland"),
            array("Fortrose","Highlands","Scotland"),
            array("Grantown on Spey","Highlands","Scotland"),
            array("Invergordon","Highlands","Scotland"),
            array("Inverlochy","Highlands","Scotland"),
            array("Inverness","Highlands","Scotland"),
            array("Kingussie","Highlands","Scotland"),
            array("Mallaig","Highlands","Scotland"),
            array("Nairn","Highlands","Scotland"),
            array("Portree","Highlands","Scotland"),
            array("Tain","Highlands","Scotland"),
            array("Thurso","Highlands","Scotland"),
            array("Ullapool","Highlands","Scotland"),
            array("Wick","Highlands","Scotland"),
            array("Bathgate","Lothian","Scotland"),
            array("Dalkeith","Lothian","Scotland"),
            array("Dunbar","Lothian","Scotland"),
            array("Edinburgh","Lothian","Scotland"),
            array("Haddington","Lothian","Scotland"),
            array("Linlithgow","Lothian","Scotland"),
            array("Loanhead","Lothian","Scotland"),
            array("Musselburgh","Lothian","Scotland"),
            array("North Berwick","Lothian","Scotland"),
            array("Penicuik","Lothian","Scotland"),
            array("Buckie","Moray","Scotland"),
            array("Cullen","Moray","Scotland"),
            array("Dufftown","Moray","Scotland"),
            array("Elgin","Moray","Scotland"),
            array("Forres","Moray","Scotland"),
            array("Keith","Moray","Scotland"),
            array("Lossiemouth","Moray","Scotland"),
            array("Aberfeldy","Perth and Kinross","Scotland"),
            array("Auchterarder","Perth and Kinross","Scotland"),
            array("Birnam","Perth and Kinross","Scotland"),
            array("Blackford","Perth and Kinross","Scotland"),
            array("Blair Atholl","Perth and Kinross","Scotland"),
            array("Blairgowrie and Rattray","Perth and Kinross","Scotland"),
            array("Bridge of Earn","Perth and Kinross","Scotland"),
            array("Bridge of Tilt","Perth and Kinross","Scotland"),
            array("Comrie","Perth and Kinross","Scotland"),
            array("Coupar Angus","Perth and Kinross","Scotland"),
            array("Crieff","Perth and Kinross","Scotland"),
            array("Kinross","Perth and Kinross","Scotland"),
            array("Perth","Perth and Kinross","Scotland"),
            array("Pitlochry","Perth and Kinross","Scotland"),
            array("Scone","Perth and Kinross","Scotland"),
            array("Coldstream","Scottish Borders","Scotland"),
            array("Duns","Scottish Borders","Scotland"),
            array("Earlston","Scottish Borders","Scotland"),
            array("Eyemouth","Scottish Borders","Scotland"),
            array("Galashiels","Scottish Borders","Scotland"),
            array("Greenlaw","Scottish Borders","Scotland"),
            array("Hawick","Scottish Borders","Scotland"),
            array("Innerleithen","Scottish Borders","Scotland"),
            array("Jedburgh","Scottish Borders","Scotland"),
            array("Kelso","Scottish Borders","Scotland"),
            array("Lauder","Scottish Borders","Scotland"),
            array("Melrose","Scottish Borders","Scotland"),
            array("Newtown St Boswells","Scottish Borders","Scotland"),
            array("Peebles","Scottish Borders","Scotland"),
            array("Selkirk","Scottish Borders","Scotland"),
            array("Bridge of Allan","Stirlingshire","Scotland"),
            array("Callander","Stirlingshire","Scotland"),
            array("Doune","Stirlingshire","Scotland"),
            array("Dunblane","Stirlingshire","Scotland"),
            array("Stirling","Stirlingshire","Scotland"),
            array("Airdrie","Strathclyde","Scotland"),
            array("Ayr","Strathclyde","Scotland"),
            array("Barrhead","Strathclyde","Scotland"),
            array("Bearsden","Strathclyde","Scotland"),
            array("Bellshill","Strathclyde","Scotland"),
            array("Biggar","Strathclyde","Scotland"),
            array("Campbeltown","Strathclyde","Scotland"),
            array("Carluke","Strathclyde","Scotland"),
            array("Clydebank","Strathclyde","Scotland"),
            array("Coatbridge","Strathclyde","Scotland"),
            array("Cumbernauld","Strathclyde","Scotland"),
            array("Dumbarton","Strathclyde","Scotland"),
            array("Dunoon","Strathclyde","Scotland"),
            array("East Kilbride","Strathclyde","Scotland"),
            array("Glasgow","Strathclyde","Scotland"),
            array("Gourock","Strathclyde","Scotland"),
            array("Greenock","Strathclyde","Scotland"),
            array("Hamilton","Strathclyde","Scotland"),
            array("Helensburgh","Strathclyde","Scotland"),
            array("Inveraray","Strathclyde","Scotland"),
            array("Irvine","Strathclyde","Scotland"),
            array("Johnstone","Strathclyde","Scotland"),
            array("Kilbarchan","Strathclyde","Scotland"),
            array("Kilmarnock","Strathclyde","Scotland"),
            array("Kilwinning","Strathclyde","Scotland"),
            array("Lanark","Strathclyde","Scotland"),
            array("Largs","Strathclyde","Scotland"),
            array("Lochgilphead","Strathclyde","Scotland"),
            array("Maybole","Strathclyde","Scotland"),
            array("Milngavie","Strathclyde","Scotland"),
            array("Motherwell","Strathclyde","Scotland"),
            array("Oban","Strathclyde","Scotland"),
            array("Paisley","Strathclyde","Scotland"),
            array("Prestwick","Strathclyde","Scotland"),
            array("Rothesay","Strathclyde","Scotland"),
            array("Rutherglen","Strathclyde","Scotland"),
            array("Saltcoats","Strathclyde","Scotland"),
            array("Tobermory","Strathclyde","Scotland"),
            array("Troon","Strathclyde","Scotland"),
            array("Armadale","West Lothian","Scotland"),
            array("Ballencrieff","West Lothian","Scotland"),
            array("Bathgate","West Lothian","Scotland"),
            array("Blackburn","West Lothian","Scotland"),
            array("Blackridge","West Lothian","Scotland"),
            array("Bridgend","West Lothian","Scotland"),
            array("Broxburn","West Lothian","Scotland"),
            array("East Calder","West Lothian","Scotland"),
            array("Linlithgow","West Lothian","Scotland"),
            array("Livingston","West Lothian","Scotland"),
            array("Stoneyburn","West Lothian","Scotland"),
            array("Whitburn","West Lothian","Scotland"),
            array("Kirkwall","Western Isles","Scotland"),
            array("Lerwick","Western Isles","Scotland"),
            array("Stornoway","Western Isles","Scotland"),
            array("Amlwch","Anglesey","Wales"),
            array("Beaumaris","Anglesey","Wales"),
            array("Benllech","Anglesey","Wales"),
            array("Holyhead","Anglesey","Wales"),
            array("Llanfairpwllgwyngyll","Anglesey","Wales"),
            array("Llangefni","Anglesey","Wales"),
            array("Menai Bridge","Anglesey","Wales"),
            array("Ammanford","Carmarthenshire","Wales"),
            array("Burry Port","Carmarthenshire","Wales"),
            array("Carmarthen","Carmarthenshire","Wales"),
            array("Garnant","Carmarthenshire","Wales"),
            array("Glanamman","Carmarthenshire","Wales"),
            array("Kidwelly","Carmarthenshire","Wales"),
            array("Laugharne","Carmarthenshire","Wales"),
            array("Llandeilo","Carmarthenshire","Wales"),
            array("Llandovery","Carmarthenshire","Wales"),
            array("Llanelli","Carmarthenshire","Wales"),
            array("Newcastle Emlyn","Carmarthenshire","Wales"),
            array("St Clears","Carmarthenshire","Wales"),
            array("Whitland","Carmarthenshire","Wales"),
            array("Aberaeron","Ceredigion","Wales"),
            array("Aberystwyth","Ceredigion","Wales"),
            array("Cardigan","Ceredigion","Wales"),
            array("Lampeter","Ceredigion","Wales"),
            array("Llandysul","Ceredigion","Wales"),
            array("New Quay","Ceredigion","Wales"),
            array("Tregaron","Ceredigion","Wales"),
            array("Abergele","Conwy","Wales"),
            array("Colwyn Bay","Conwy","Wales"),
            array("Conwy","Conwy","Wales"),
            array("Deganwy","Conwy","Wales"),
            array("Llandudno","Conwy","Wales"),
            array("Llandudno Junction","Conwy","Wales"),
            array("Llanfairfechan","Conwy","Wales"),
            array("Llanrwst","Conwy","Wales"),
            array("Old Colwyn","Conwy","Wales"),
            array("Penmaenmawr","Conwy","Wales"),
            array("Towyn","Conwy","Wales"),
            array("Carrog","Denbighshire","Wales"),
            array("Corwen","Denbighshire","Wales"),
            array("Denbigh","Denbighshire","Wales"),
            array("Gellifor","Denbighshire","Wales"),
            array("Llangollen","Denbighshire","Wales"),
            array("Prestatyn","Denbighshire","Wales"),
            array("Rhuddlan","Denbighshire","Wales"),
            array("Rhyl","Denbighshire","Wales"),
            array("Ruthin","Denbighshire","Wales"),
            array("St Asaph","Denbighshire","Wales"),
            array("Bagallit","Flintshire","Wales"),
            array("Broughton","Flintshire","Wales"),
            array("Buckley","Flintshire","Wales"),
            array("Caerwys","Flintshire","Wales"),
            array("Connah's Quay","Flintshire","Wales"),
            array("Ewole","Flintshire","Wales"),
            array("Flint","Flintshire","Wales"),
            array("Hawarden","Flintshire","Wales"),
            array("Holywell","Flintshire","Wales"),
            array("Mold","Flintshire","Wales"),
            array("Queensferry","Flintshire","Wales"),
            array("Saltney","Flintshire","Wales"),
            array("Shotton","Flintshire","Wales"),
            array("Abertillery","Gwent","Wales"),
            array("Blaina","Gwent","Wales"),
            array("Brynmawr","Gwent","Wales"),
            array("Cwmbran","Gwent","Wales"),
            array("Ebbw Vale","Gwent","Wales"),
            array("Newport","Gwent","Wales"),
            array("Tafarnaubach","Gwent","Wales"),
            array("Tredegar","Gwent","Wales"),
            array("Bala","Gwynedd","Wales"),
            array("Bangor","Gwynedd","Wales"),
            array("Barmouth","Gwynedd","Wales"),
            array("Beaumaris","Gwynedd","Wales"),
            array("Betws y Coed","Gwynedd","Wales"),
            array("Blaenau Ffestiniog","Gwynedd","Wales"),
            array("Caernarfon","Gwynedd","Wales"),
            array("Conwy","Gwynedd","Wales"),
            array("Criccieth","Gwynedd","Wales"),
            array("Dolgellau","Gwynedd","Wales"),
            array("Ffestiniog","Gwynedd","Wales"),
            array("Harlech","Gwynedd","Wales"),
            array("Holyhead","Gwynedd","Wales"),
            array("Llanberis","Gwynedd","Wales"),
            array("Llanfachreth","Gwynedd","Wales"),
            array("Nefyn","Gwynedd","Wales"),
            array("Porthmadog","Gwynedd","Wales"),
            array("Pwllheli","Gwynedd","Wales"),
            array("Tywyn","Gwynedd","Wales"),
            array("Y Felinheli","Gwynedd","Wales"),
            array("Aberdare","Mid Glamorgan","Wales"),
            array("Bridgend","Mid Glamorgan","Wales"),
            array("Caerphilly","Mid Glamorgan","Wales"),
            array("Llantrisant","Mid Glamorgan","Wales"),
            array("Maesteg","Mid Glamorgan","Wales"),
            array("Merthyr Tydfil","Mid Glamorgan","Wales"),
            array("Pontypridd","Mid Glamorgan","Wales"),
            array("Porth","Mid Glamorgan","Wales"),
            array("Porthcawl","Mid Glamorgan","Wales"),
            array("Abergavenny","Monmouthshire","Wales"),
            array("Caldicot","Monmouthshire","Wales"),
            array("Chepstow","Monmouthshire","Wales"),
            array("Monmouth","Monmouthshire","Wales"),
            array("Usk","Monmouthshire","Wales"),
            array("Fishguard","Pembrokeshire","Wales"),
            array("Goodwick","Pembrokeshire","Wales"),
            array("Hakin","Pembrokeshire","Wales"),
            array("Haverfordwest","Pembrokeshire","Wales"),
            array("Milford Haven","Pembrokeshire","Wales"),
            array("Narberth","Pembrokeshire","Wales"),
            array("Newport","Pembrokeshire","Wales"),
            array("Neyland","Pembrokeshire","Wales"),
            array("Pembroke","Pembrokeshire","Wales"),
            array("Pembroke Dock","Pembrokeshire","Wales"),
            array("St Davids","Pembrokeshire","Wales"),
            array("Tenby","Pembrokeshire","Wales"),
            array("Brecon","Powys","Wales"),
            array("Builth Wells","Powys","Wales"),
            array("Cefnllys","Powys","Wales"),
            array("Crickhowell","Powys","Wales"),
            array("Hay on Wye","Powys","Wales"),
            array("Knighton","Powys","Wales"),
            array("Llandrindod Wells","Powys","Wales"),
            array("Llanfair Caereinion","Powys","Wales"),
            array("Llanfyllin","Powys","Wales"),
            array("Llangors","Powys","Wales"),
            array("Llanidloes","Powys","Wales"),
            array("Llanwrtyd Wells","Powys","Wales"),
            array("Machynlleth","Powys","Wales"),
            array("Montgomery","Powys","Wales"),
            array("Newtown","Powys","Wales"),
            array("Old Radnor","Powys","Wales"),
            array("Presteigne","Powys","Wales"),
            array("Rhayader","Powys","Wales"),
            array("Talgarth","Powys","Wales"),
            array("Welshpool","Powys","Wales"),
            array("Barry","South Glamorgan","Wales"),
            array("Cardiff","South Glamorgan","Wales"),
            array("Cowbridge","South Glamorgan","Wales"),
            array("Llantwit Major","South Glamorgan","Wales"),
            array("Penarth","South Glamorgan","Wales"),
            array("Gorseinon","West Glamorgan","Wales"),
            array("Lliw Valey","West Glamorgan","Wales"),
            array("Neath","West Glamorgan","Wales"),
            array("Port Talbot","West Glamorgan","Wales"),
            array("Swansea","West Glamorgan","Wales"),
            array("Chirk","Wrexham","Wales"),
            array("Overton on Dee","Wrexham","Wales"),
            array("Rhosllannerchrugog","Wrexham","Wales"),
            array("Rhosnesni","Wrexham","Wales"),
            array("Wrexham","Wrexham","Wales")
          );
    }
}