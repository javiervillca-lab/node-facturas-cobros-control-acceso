FROM postgres:16.1-alpine as base

FROM base as dev
# Install required dependencies
RUN apk --no-cache add \
    python3 \
    py3-pip \
    cmake \
    make \
    gcc \
    g++ \
    clang15 \
    llvm15 \
    postgresql-dev

RUN pip install pgxnclient --break-system-packages

RUN pgxn install temporal_tables

## Cleanup to reduce image size
RUN apk del \
    python3 \
    py3-pip \
    cmake \
    make \
    gcc \
    g++ \
    clang15 \
    llvm15 \
    postgresql-dev \
    && rm -rf /var/cache/apk/* \
    && rm -rf /root/.cache \
    && rm -rf /root/.pgxn

RUN apk add --no-cache tzdata
ENV TZ=America/La_Paz