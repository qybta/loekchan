CREATE TABLE threads (
	threadid serial primary key not null,
	board text not null,
	issticky bool not null default 'f',
	locked bool not null default 'f',
	modtime timestamptz not null default now()
);
CREATE INDEX thread_order ON threads (board, modtime);

CREATE TABLE posts (
	postid serial primary key not null,
	createtime timestamptz not null default now(),
	threadid int not null references threads on delete cascade,
	name text,
	mail text,
	trip bytea,
	post text,
	-- file fields:
	filemime text,
	-- image/gif, image/png, image/jpeg
	filename text, -- the name at time of upload
	filesize int,
	filesha1 bytea,
	attachment bytea,
	-- image fields:
	thumbnail bytea, -- always jpeg
	imagex int,
	imagey int
);
CREATE UNIQUE INDEX post_filesha1 ON posts (filesha1) where filesha1 is not null;
