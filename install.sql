CREATE TABLE IF NOT EXISTS oc_jostpay(
				id int not null auto_increment,
				primary key(id),
				order_id INT NOT NULL,unique(order_id),
				date_time datetime,
				transaction_id VARCHAR(48),
				approved_amount VARCHAR(12),
				customer_email VARCHAR(68),
				response_description VARCHAR(225),
				response_code VARCHAR(5),
				transaction_amount varchar(12),
				customer_id INT
				)"