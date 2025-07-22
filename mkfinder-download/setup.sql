-- MKfinder Database Setup Script
-- PostgreSQL schema for bird species identification system

-- Create species table
CREATE TABLE IF NOT EXISTS species (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    scientific_name VARCHAR(255),
    description TEXT,
    characteristics JSONB,
    habitat TEXT,
    diet TEXT,
    behavior TEXT,
    conservation_status VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create uploads table
CREATE TABLE IF NOT EXISTS uploads (
    id SERIAL PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_size INTEGER,
    mime_type VARCHAR(100),
    upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create identifications table
CREATE TABLE IF NOT EXISTS identifications (
    id SERIAL PRIMARY KEY,
    identification_id VARCHAR(255) UNIQUE,
    upload_id INTEGER REFERENCES uploads(id),
    species_name VARCHAR(255),
    confidence DECIMAL(5,2),
    identification_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (species_name) REFERENCES species(name) ON UPDATE CASCADE
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_species_name ON species(name);
CREATE INDEX IF NOT EXISTS idx_identifications_species ON identifications(species_name);
CREATE INDEX IF NOT EXISTS idx_identifications_time ON identifications(identification_time DESC);
CREATE INDEX IF NOT EXISTS idx_uploads_time ON uploads(upload_time DESC);

-- Insert initial species data
INSERT INTO species (name, scientific_name, description, characteristics, habitat, diet, behavior, conservation_status) VALUES 
('American Robin', 'Turdus migratorius', 'The American Robin is a migratory songbird of the true thrush genus and Turdidae, the wider thrush family. It is named after the European robin because of its reddish-orange breast, though the two species are not closely related.', 
'{"size": "8-11 inches", "wingspan": "12-16 inches", "weight": "2.7-3.0 oz", "colors": ["orange-red breast", "dark gray head", "brown back"], "distinctive_features": ["bright orange-red breast", "white eye ring", "yellow bill"]}',
'Found in woodlands, suburban areas, parks, and gardens. Prefers areas with trees for nesting and open ground for foraging.',
'Primarily earthworms and insects, but also fruits and berries, especially in fall and winter.',
'Often seen hopping on lawns searching for worms. Known for their melodic song, especially at dawn. Builds cup-shaped nests in trees.',
'Least Concern'),

('Blue Jay', 'Cyanocitta cristata', 'The Blue Jay is a passerine bird in the family Corvidae, native to eastern North America. It is resident through most of eastern and central United States, though western populations may be migratory.', 
'{"size": "11-12 inches", "wingspan": "13-17 inches", "weight": "2.5-3.5 oz", "colors": ["bright blue upperparts", "white underparts", "black markings"], "distinctive_features": ["prominent blue crest", "black necklace marking", "white patches on wings and tail"]}',
'Deciduous and mixed forests, woodland edges, parks, and suburban areas with mature trees.',
'Omnivorous - nuts (especially acorns), seeds, insects, occasionally eggs and nestlings of other birds.',
'Highly intelligent and social. Known for their loud calls and ability to mimic other birds. Often travels in flocks outside breeding season.',
'Least Concern'),

('Northern Cardinal', 'Cardinalis cardinalis', 'The Northern Cardinal is a bird in the genus Cardinalis. It can be found in southeastern Canada, through the eastern United States from Maine to northern Guatemala and Belize.', 
'{"size": "8.5-9 inches", "wingspan": "9.8-12.2 inches", "weight": "1.5-1.7 oz", "colors": ["bright red (male)", "warm brown with red tinges (female)"], "distinctive_features": ["prominent red crest", "thick orange-red bill", "black face mask (male)"]}',
'Woodland edges, gardens, shrublands, and swamps. Prefers areas with dense shrubs and thickets.',
'Seeds, grains, fruits, and insects. Common at bird feeders, especially enjoying sunflower seeds.',
'Non-migratory year-round residents. Males are territorial and sing from prominent perches. Known for their clear whistled songs.',
'Least Concern')
ON CONFLICT (name) DO NOTHING;

-- Create a function to update the updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Create triggers to automatically update timestamps
CREATE TRIGGER update_species_updated_at BEFORE UPDATE ON species
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Grant necessary permissions (adjust as needed for your setup)
-- GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO your_database_user;
-- GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO your_database_user;