# /// script
# dependencies = [
#     "sqlalchemy",
#     "pymysql",
# ]
# ///

import json
from sqlalchemy import Column, ForeignKey, Integer, String, create_engine
from sqlalchemy.orm import declarative_base, relationship, sessionmaker

Base = declarative_base()


# Define Database Models
class Province(Base):
  __tablename__ = 'provinces'
  id = Column(Integer, primary_key=True, autoincrement=True)
  name = Column(String(100), unique=True)
  districts = relationship('District', back_populates='province')


class District(Base):
  __tablename__ = 'districts'
  id = Column(Integer, primary_key=True, autoincrement=True)
  name = Column(String(100))
  province_id = Column(Integer, ForeignKey('provinces.id'))
  province = relationship('Province', back_populates='districts')
  sectors = relationship('Sector', back_populates='district')


class Sector(Base):
  __tablename__ = 'sectors'
  id = Column(Integer, primary_key=True, autoincrement=True)
  name = Column(String(100))
  district_id = Column(Integer, ForeignKey('districts.id'))
  district = relationship('District', back_populates='sectors')
  cells = relationship('Cell', back_populates='sector')


class Cell(Base):
  __tablename__ = 'cells'
  id = Column(Integer, primary_key=True, autoincrement=True)
  name = Column(String(100))
  sector_id = Column(Integer, ForeignKey('sectors.id'))
  sector = relationship('Sector', back_populates='cells')
  villages = relationship('Village', back_populates='cell')


class Village(Base):
  __tablename__ = 'villages'
  id = Column(Integer, primary_key=True, autoincrement=True)
  name = Column(String(100))
  cell_id = Column(Integer, ForeignKey('cells.id'))
  cell = relationship('Cell', back_populates='villages')


# Configure MySQL Connection URL
# Replace root, your_password, localhost, 3306, and rwanda with your actual settings
DATABASE_URL = 'mysql+pymysql://admin:Ingeri%4049276@localhost:3306/rwanda?charset=utf8mb4'

engine = create_engine(DATABASE_URL, echo=False)

# Create tables in the MySQL database
Base.metadata.create_all(engine)

Session = sessionmaker(bind=engine)
session = Session()

# Load the JSON data source
with open('data.json', 'r', encoding='utf-8') as f:
  data = json.load(f)

# Parse and Insert Data hierarchically
for province_name, districts in data.items():
  province = Province(name=province_name)
  session.add(province)

  for district_name, sectors in districts.items():
    district = District(name=district_name, province=province)
    session.add(district)

    for sector_name, cells in sectors.items():
      sector = Sector(name=sector_name, district=district)
      session.add(sector)

      for cell_name, villages in cells.items():
        cell = Cell(name=cell_name, sector=sector)
        session.add(cell)

        for village_name in villages:
          village = Village(name=village_name, cell=cell)
          session.add(village)

# Commit changes and close session
session.commit()
session.close()

print('Data successfully saved to the MySQL database!')