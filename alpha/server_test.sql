use alpha;

create table server_test_report (
    id          integer         not null auto_increment,
    userid      integer         not null,
    version     varchar(64)    not null,
    platform    varchar(64)    not null,
    test_group  varchar(64)    not null,
    status      integer         not null,
    comment     text,
    mod_time    timestamp,
    product_name    varchar(64) not null,
    primary key (id)
);

alter table server_test_report
    add unique (userid, version, platform, test_group),
    add index str_vers (version);
