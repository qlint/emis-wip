--
-- PostgreSQL database dump
--

-- Dumped from database version 12.1
-- Dumped by pg_dump version 12.1

-- Started on 2020-10-21 01:27:32

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 3332 (class 0 OID 78362)
-- Dependencies: 283
-- Data for Name: countries; Type: TABLE DATA; Schema: app; Owner: postgres
--

INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (1, 'Afghanistan', 'AF', 'AFG', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (3, 'Algeria', 'DZ', 'DZA', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (4, 'American Samoa', 'AS', 'ASM', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (5, 'Andorra', 'AD', 'AND', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (6, 'Angola', 'AO', 'AGO', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (7, 'Anguilla', 'AI', 'AIA', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (8, 'Antarctica', 'AQ', 'ATA', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (9, 'Antigua and Barbuda', 'AG', 'ATG', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (10, 'Argentina', 'AR', 'ARG', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (11, 'Armenia', 'AM', 'ARM', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (12, 'Aruba', 'AW', 'ABW', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (13, 'Australia', 'AU', 'AUS', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (14, 'Austria', 'AT', 'AUT', 5, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (15, 'Azerbaijan', 'AZ', 'AZE', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (16, 'Bahamas', 'BS', 'BHS', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (17, 'Bahrain', 'BH', 'BHR', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (18, 'Bangladesh', 'BD', 'BGD', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (19, 'Barbados', 'BB', 'BRB', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (20, 'Belarus', 'BY', 'BLR', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (21, 'Belgium', 'BE', 'BEL', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (22, 'Belize', 'BZ', 'BLZ', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (23, 'Benin', 'BJ', 'BEN', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (24, 'Bermuda', 'BM', 'BMU', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (25, 'Bhutan', 'BT', 'BTN', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (26, 'Bolivia', 'BO', 'BOL', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (27, 'Bosnia and Herzegowina', 'BA', 'BIH', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (28, 'Botswana', 'BW', 'BWA', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (29, 'Bouvet Island', 'BV', 'BVT', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (30, 'Brazil', 'BR', 'BRA', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (31, 'British Indian Ocean Territory', 'IO', 'IOT', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (32, 'Brunei Darussalam', 'BN', 'BRN', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (33, 'Bulgaria', 'BG', 'BGR', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (34, 'Burkina Faso', 'BF', 'BFA', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (35, 'Burundi', 'BI', 'BDI', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (36, 'Cambodia', 'KH', 'KHM', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (37, 'Cameroon', 'CM', 'CMR', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (39, 'Cape Verde', 'CV', 'CPV', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (40, 'Cayman Islands', 'KY', 'CYM', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (41, 'Central African Republic', 'CF', 'CAF', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (42, 'Chad', 'TD', 'TCD', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (43, 'Chile', 'CL', 'CHL', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (44, 'China', 'CN', 'CHN', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (45, 'Christmas Island', 'CX', 'CXR', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (46, 'Cocos (Keeling) Islands', 'CC', 'CCK', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (47, 'Colombia', 'CO', 'COL', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (48, 'Comoros', 'KM', 'COM', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (49, 'Congo', 'CG', 'COG', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (50, 'Cook Islands', 'CK', 'COK', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (51, 'Costa Rica', 'CR', 'CRI', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (52, 'Cote D''Ivoire', 'CI', 'CIV', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (53, 'Croatia', 'HR', 'HRV', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (54, 'Cuba', 'CU', 'CUB', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (55, 'Cyprus', 'CY', 'CYP', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (56, 'Czech Republic', 'CZ', 'CZE', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (57, 'Denmark', 'DK', 'DNK', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (58, 'Djibouti', 'DJ', 'DJI', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (59, 'Dominica', 'DM', 'DMA', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (60, 'Dominican Republic', 'DO', 'DOM', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (61, 'East Timor', 'TP', 'TMP', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (62, 'Ecuador', 'EC', 'ECU', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (63, 'Egypt', 'EG', 'EGY', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (64, 'El Salvador', 'SV', 'SLV', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (65, 'Equatorial Guinea', 'GQ', 'GNQ', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (66, 'Eritrea', 'ER', 'ERI', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (67, 'Estonia', 'EE', 'EST', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (68, 'Ethiopia', 'ET', 'ETH', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (69, 'Falkland Islands (Malvinas)', 'FK', 'FLK', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (70, 'Faroe Islands', 'FO', 'FRO', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (71, 'Fiji', 'FJ', 'FJI', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (72, 'Finland', 'FI', 'FIN', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (73, 'France', 'FR', 'FRA', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (74, 'France, Metropolitan', 'FX', 'FXX', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (75, 'French Guiana', 'GF', 'GUF', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (76, 'French Polynesia', 'PF', 'PYF', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (77, 'French Southern Territories', 'TF', 'ATF', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (78, 'Gabon', 'GA', 'GAB', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (79, 'Gambia', 'GM', 'GMB', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (80, 'Georgia', 'GE', 'GEO', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (81, 'Germany', 'DE', 'DEU', 5, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (82, 'Ghana', 'GH', 'GHA', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (83, 'Gibraltar', 'GI', 'GIB', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (84, 'Greece', 'GR', 'GRC', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (85, 'Greenland', 'GL', 'GRL', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (86, 'Grenada', 'GD', 'GRD', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (87, 'Guadeloupe', 'GP', 'GLP', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (88, 'Guam', 'GU', 'GUM', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (89, 'Guatemala', 'GT', 'GTM', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (90, 'Guinea', 'GN', 'GIN', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (91, 'Guinea-bissau', 'GW', 'GNB', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (92, 'Guyana', 'GY', 'GUY', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (93, 'Haiti', 'HT', 'HTI', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (94, 'Heard and Mc Donald Islands', 'HM', 'HMD', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (95, 'Honduras', 'HN', 'HND', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (96, 'Hong Kong', 'HK', 'HKG', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (97, 'Hungary', 'HU', 'HUN', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (98, 'Iceland', 'IS', 'ISL', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (99, 'India', 'IN', 'IND', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (100, 'Indonesia', 'ID', 'IDN', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (101, 'Iran (Islamic Republic of)', 'IR', 'IRN', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (102, 'Iraq', 'IQ', 'IRQ', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (103, 'Ireland', 'IE', 'IRL', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (104, 'Israel', 'IL', 'ISR', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (105, 'Italy', 'IT', 'ITA', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (106, 'Jamaica', 'JM', 'JAM', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (107, 'Japan', 'JP', 'JPN', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (108, 'Jordan', 'JO', 'JOR', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (109, 'Kazakhstan', 'KZ', 'KAZ', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (111, 'Kiribati', 'KI', 'KIR', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (112, 'Korea, Democratic People''s Republic of', 'KP', 'PRK', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (113, 'Korea, Republic of', 'KR', 'KOR', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (114, 'Kuwait', 'KW', 'KWT', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (115, 'Kyrgyzstan', 'KG', 'KGZ', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (116, 'Lao People''s Democratic Republic', 'LA', 'LAO', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (117, 'Latvia', 'LV', 'LVA', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (118, 'Lebanon', 'LB', 'LBN', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (119, 'Lesotho', 'LS', 'LSO', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (120, 'Liberia', 'LR', 'LBR', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (121, 'Libyan Arab Jamahiriya', 'LY', 'LBY', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (122, 'Liechtenstein', 'LI', 'LIE', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (123, 'Lithuania', 'LT', 'LTU', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (124, 'Luxembourg', 'LU', 'LUX', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (125, 'Macau', 'MO', 'MAC', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (126, 'Macedonia, The Former Yugoslav Republic of', 'MK', 'MKD', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (127, 'Madagascar', 'MG', 'MDG', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (128, 'Malawi', 'MW', 'MWI', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (129, 'Malaysia', 'MY', 'MYS', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (130, 'Maldives', 'MV', 'MDV', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (131, 'Mali', 'ML', 'MLI', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (132, 'Malta', 'MT', 'MLT', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (133, 'Marshall Islands', 'MH', 'MHL', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (134, 'Martinique', 'MQ', 'MTQ', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (135, 'Mauritania', 'MR', 'MRT', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (136, 'Mauritius', 'MU', 'MUS', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (137, 'Mayotte', 'YT', 'MYT', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (138, 'Mexico', 'MX', 'MEX', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (139, 'Micronesia, Federated States of', 'FM', 'FSM', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (140, 'Moldova, Republic of', 'MD', 'MDA', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (141, 'Monaco', 'MC', 'MCO', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (142, 'Mongolia', 'MN', 'MNG', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (143, 'Montserrat', 'MS', 'MSR', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (144, 'Morocco', 'MA', 'MAR', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (145, 'Mozambique', 'MZ', 'MOZ', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (146, 'Myanmar', 'MM', 'MMR', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (147, 'Namibia', 'NA', 'NAM', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (148, 'Nauru', 'NR', 'NRU', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (149, 'Nepal', 'NP', 'NPL', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (150, 'Netherlands', 'NL', 'NLD', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (151, 'Netherlands Antilles', 'AN', 'ANT', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (152, 'New Caledonia', 'NC', 'NCL', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (153, 'New Zealand', 'NZ', 'NZL', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (154, 'Nicaragua', 'NI', 'NIC', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (155, 'Niger', 'NE', 'NER', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (156, 'Nigeria', 'NG', 'NGA', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (157, 'Niue', 'NU', 'NIU', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (158, 'Norfolk Island', 'NF', 'NFK', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (159, 'Northern Mariana Islands', 'MP', 'MNP', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (160, 'Norway', 'NO', 'NOR', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (161, 'Oman', 'OM', 'OMN', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (162, 'Pakistan', 'PK', 'PAK', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (163, 'Palau', 'PW', 'PLW', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (164, 'Panama', 'PA', 'PAN', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (165, 'Papua New Guinea', 'PG', 'PNG', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (166, 'Paraguay', 'PY', 'PRY', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (167, 'Peru', 'PE', 'PER', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (168, 'Philippines', 'PH', 'PHL', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (169, 'Pitcairn', 'PN', 'PCN', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (170, 'Poland', 'PL', 'POL', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (171, 'Portugal', 'PT', 'PRT', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (172, 'Puerto Rico', 'PR', 'PRI', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (173, 'Qatar', 'QA', 'QAT', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (174, 'Reunion', 'RE', 'REU', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (175, 'Romania', 'RO', 'ROM', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (176, 'Russian Federation', 'RU', 'RUS', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (177, 'Rwanda', 'RW', 'RWA', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (178, 'Saint Kitts and Nevis', 'KN', 'KNA', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (179, 'Saint Lucia', 'LC', 'LCA', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (180, 'Saint Vincent and the Grenadines', 'VC', 'VCT', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (181, 'Samoa', 'WS', 'WSM', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (182, 'San Marino', 'SM', 'SMR', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (183, 'Sao Tome and Principe', 'ST', 'STP', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (184, 'Saudi Arabia', 'SA', 'SAU', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (185, 'Senegal', 'SN', 'SEN', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (186, 'Seychelles', 'SC', 'SYC', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (187, 'Sierra Leone', 'SL', 'SLE', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (188, 'Singapore', 'SG', 'SGP', 4, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (189, 'Slovakia (Slovak Republic)', 'SK', 'SVK', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (190, 'Slovenia', 'SI', 'SVN', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (191, 'Solomon Islands', 'SB', 'SLB', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (192, 'Somalia', 'SO', 'SOM', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (193, 'South Africa', 'ZA', 'ZAF', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (194, 'South Georgia and the South Sandwich Islands', 'GS', 'SGS', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (195, 'Spain', 'ES', 'ESP', 3, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (196, 'Sri Lanka', 'LK', 'LKA', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (197, 'St. Helena', 'SH', 'SHN', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (198, 'St. Pierre and Miquelon', 'PM', 'SPM', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (199, 'Sudan', 'SD', 'SDN', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (200, 'Suriname', 'SR', 'SUR', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (201, 'Svalbard and Jan Mayen Islands', 'SJ', 'SJM', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (202, 'Swaziland', 'SZ', 'SWZ', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (203, 'Sweden', 'SE', 'SWE', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (204, 'Switzerland', 'CH', 'CHE', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (205, 'Syrian Arab Republic', 'SY', 'SYR', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (206, 'Taiwan', 'TW', 'TWN', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (207, 'Tajikistan', 'TJ', 'TJK', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (208, 'Tanzania, United Republic of', 'TZ', 'TZA', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (209, 'Thailand', 'TH', 'THA', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (210, 'Togo', 'TG', 'TGO', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (211, 'Tokelau', 'TK', 'TKL', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (212, 'Tonga', 'TO', 'TON', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (213, 'Trinidad and Tobago', 'TT', 'TTO', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (214, 'Tunisia', 'TN', 'TUN', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (215, 'Turkey', 'TR', 'TUR', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (216, 'Turkmenistan', 'TM', 'TKM', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (217, 'Turks and Caicos Islands', 'TC', 'TCA', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (218, 'Tuvalu', 'TV', 'TUV', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (220, 'Ukraine', 'UA', 'UKR', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (221, 'United Arab Emirates', 'AE', 'ARE', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (222, 'United Kingdom', 'GB', 'GBR', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (223, 'United States', 'US', 'USA', 2, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (224, 'United States Minor Outlying Islands', 'UM', 'UMI', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (225, 'Uruguay', 'UY', 'URY', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (226, 'Uzbekistan', 'UZ', 'UZB', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (227, 'Vanuatu', 'VU', 'VUT', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (228, 'Vatican City State (Holy See)', 'VA', 'VAT', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (229, 'Venezuela', 'VE', 'VEN', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (230, 'Viet Nam', 'VN', 'VNM', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (231, 'Virgin Islands (British)', 'VG', 'VGB', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (232, 'Virgin Islands (U.S.)', 'VI', 'VIR', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (233, 'Wallis and Futuna Islands', 'WF', 'WLF', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (234, 'Western Sahara', 'EH', 'ESH', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (235, 'Yemen', 'YE', 'YEM', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (236, 'Yugoslavia', 'YU', 'YUG', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (237, 'Zaire', 'ZR', 'ZAR', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (238, 'Zambia', 'ZM', 'ZMB', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (239, 'Zimbabwe', 'ZW', 'ZWE', 1, NULL, NULL, NULL);
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (110, 'Kenya', 'KE', 'KEN', 1, 'Kenyan Shilling', 'KSH', '8-4-4,I.G.C.S.E,Montessori,Dual Curriculum (8-4-4/IGCSE)');
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (38, 'Canada', 'CA', 'CAN', 1, 'Canadian Dollar', 'CAD', 'ONTARIO K8,ONTARIO K12');
INSERT INTO app.countries (countries_id, countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, currency_name, currency_symbol, curriculum) VALUES (219, 'Uganda', 'UG', 'UGA', 1, 'Ugandan Shilling', 'USH', '7-4-2-4,I.G.C.S.E,Montessori,Dual Curriculum');


--
-- TOC entry 3340 (class 0 OID 0)
-- Dependencies: 284
-- Name: countries_countries_id_seq; Type: SEQUENCE SET; Schema: app; Owner: postgres
--

SELECT pg_catalog.setval('app.countries_countries_id_seq', 239, true);


-- Completed on 2020-10-21 01:27:33

--
-- PostgreSQL database dump complete
--

